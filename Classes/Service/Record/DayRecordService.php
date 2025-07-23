<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Service\Record;

use Doctrine\DBAL\Exception;
use JWeiland\Events2\Traits\RelationHandlerTrait;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

readonly class DayRecordService
{
    use RelationHandlerTrait;

    private const TABLE = 'tx_events2_domain_model_day';

    public function __construct(
        private ConnectionPool $connectionPool,
        private TcaSchemaFactory $tcaSchemaFactory,
        private ReferenceIndex $referenceIndex,
    ) {}

    public function getByEventAndTime(int $eventUid, int $timestamp): array
    {
        $queryBuilder = $this->getQueryBuilder();

        try {
            $day = $queryBuilder
                ->select('*')
                ->from(self::TABLE, 'd')
                ->where(
                    $queryBuilder->expr()->eq(
                        'd.event',
                        $queryBuilder->createNamedParameter($eventUid, Connection::PARAM_INT),
                    ),
                    $queryBuilder->expr()->eq(
                        'd.day_time',
                        $queryBuilder->createNamedParameter($timestamp, Connection::PARAM_INT),
                    ),
                )
                ->executeQuery()
                ->fetchAssociative();
        } catch (Exception) {
            return [];
        }

        return is_array($day) ? $day : [];
    }

    /**
     * Removes all day records linked to the specified event record. If the event record is versioned,
     * only day records within the same workspace will be deleted. Since the "event" column may contain
     * various translated or versioned event UIDs, the more stable "def_lang_event_uid" column is used instead,
     * as it consistently references the event UID in the default workspace and default language.
     *
     * @param int $eventUid Provide the UID of the event record in the default language of the current workspace
     */
    public function removeAllByEventUid(int $eventUid): void
    {
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE);
        try {
            $dayRecordsToDelete = $connection->select(
                ['uid'],
                self::TABLE,
                [
                    'def_lang_event_uid' => $eventUid,
                    't3ver_wsid' => $this->getBackendUser()->workspace,
                ],
            )->fetchAllAssociative();

            foreach ($dayRecordsToDelete as $dayRecordToDelete) {
                $connection->delete(self::TABLE, ['uid' => $dayRecordToDelete['uid']]);
                $this->referenceIndex->updateRefIndexTable(self::TABLE, $dayRecordToDelete['uid'], false, $this->getBackendUser()->workspace);
            }
        } catch (Exception) {
        }
    }

    public function bulkInsertAllDayRecords(array $dayRecords, int $eventUid, array $languageUids): void
    {
        if ($dayRecords === []) {
            return;
        }

        $this->createVersionRecords($dayRecords, $eventUid);

        foreach ($languageUids as $languageUid) {
            $this->createVersionRecords($dayRecords, $eventUid, $languageUid);
        }
    }

    protected function createVersionRecords(array $newDayRecords, int $eventUid, int $languageUid = 0): void
    {
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE);

        if ($languageUid > 0) {
            $eventLocalizations = BackendUtility::getRecordLocalization('tx_events2_domain_model_event', $eventUid, $languageUid, 'AND t3ver_oid=0');
            $eventRecord = reset($eventLocalizations);
        } else {
            $eventRecord = BackendUtility::getRecord('tx_events2_domain_model_event', $eventUid);
        }

        // The day records in the default language are required solely for populating the l10n_parent field
        // in translated records.
        $dayRecordsInDefaultLanguage = [];
        if ($languageUid > 0) {
            $dayRecordsInDefaultLanguage = $connection->select(
                ['*'],
                self::TABLE,
                [
                    'sys_language_uid' => 0,
                    't3ver_wsid' => $this->getBackendUser()->workspace,
                ],
            )->fetchAllAssociative();
        }

        // To fill up the t3ver_oid and t3_origuid we need the day records from LIVE workspace
        $existingLiveDayRecords = $this->getExistingDayRecords((int)$eventRecord['uid'], 0);

        $lastDayRecordKey = $this->enrichNewDayRecords(
            $newDayRecords,
            $dayRecordsInDefaultLanguage,
            $existingLiveDayRecords,
            $eventRecord,
            $eventUid,
            $languageUid,
        );

        if ($newDayRecords !== []) {
            $connection->bulkInsert(
                self::TABLE,
                $newDayRecords,
                array_keys(current($newDayRecords)),
            );

            // Update the reference index to ensure that the Workspace module correctly reflects recent modifications
            foreach ($this->getExistingDayRecords((int)$eventRecord['uid']) as $newDayRecord) {
                $this->referenceIndex->updateRefIndexTable(self::TABLE, $newDayRecord['uid'], false, $this->getBackendUser()->workspace);
            }
        }

        // If there are fewer new day records compared to the LIVE workspace, versioned day records must be added
        // to mark the day record as DELETED.
        $existingLiveDayRecordsToBeMarkedAsDeleted = array_filter(
            $existingLiveDayRecords,
            fn($dayRecordOfLive, $dayRecordKeyOfLive) => $dayRecordKeyOfLive > $lastDayRecordKey,
            ARRAY_FILTER_USE_BOTH,
        );

        foreach ($existingLiveDayRecordsToBeMarkedAsDeleted as $existingLiveDayRecord) {
            $existingLiveDayRecord['t3_origuid'] = (int)$existingLiveDayRecord['uid'];
            $existingLiveDayRecord['t3ver_oid'] = (int)$existingLiveDayRecord['uid'];
            $existingLiveDayRecord['t3ver_wsid'] = $this->getBackendUser()->workspace;
            $existingLiveDayRecord['t3ver_state'] = VersionState::DELETE_PLACEHOLDER->value;
            unset($existingLiveDayRecord['uid']);

            $this->referenceIndex->updateRefIndexTable(
                self::TABLE,
                $connection->insert(self::TABLE, $existingLiveDayRecord),
                false,
                $this->getBackendUser()->workspace,
            );
        }

        // Multiple day records associated with the event have been removed and added. Reference index has been updated accordingly.
        $workspaceVersionOfEventRecord = $eventRecord;
        BackendUtility::workspaceOL('tx_events2_domain_model_event', $workspaceVersionOfEventRecord);

        if (is_array($workspaceVersionOfEventRecord)) {
            $this->referenceIndex->updateRefIndexTable(
                'tx_events2_domain_model_event',
                (int)($workspaceVersionOfEventRecord['_ORIG_uid'] ?? $workspaceVersionOfEventRecord['uid']),
                false,
                $this->getBackendUser()->workspace,
            );
        }
    }

    /**
     * @param array $eventRecord This is the event record from LIVE workspace. Maybe translated. Be careful: $eventUid may differ
     * @param int $eventUid This is the event UID of the default language in LIVE workspace
     */
    protected function enrichNewDayRecords(
        array &$newDayRecords,
        array $dayRecordsInDefaultLanguage,
        array $existingLiveDayRecords,
        array $eventRecord,
        int $eventUid,
        int $languageUid,
    ): int {
        $newDayRecordKey = 0;

        foreach ($newDayRecords as $newDayRecordKey => &$newDayRecord) {
            $l10nParent = 0;
            if ($dayRecordsInDefaultLanguage !== []) {
                $l10nParent = array_key_exists(
                    $newDayRecordKey,
                    $dayRecordsInDefaultLanguage,
                ) ? (int)$dayRecordsInDefaultLanguage[$newDayRecordKey]['uid'] : 0;
            }

            $newDayRecord['tstamp'] = time();
            $newDayRecord['crdate'] = time();
            $newDayRecord['event'] = (int)$eventRecord['uid'];
            $newDayRecord['def_lang_event_uid'] = $eventUid;
            $newDayRecord['t3ver_wsid'] = $this->getBackendUser()->workspace;
            $newDayRecord['t3ver_stage'] = 0;
            $newDayRecord['sys_language_uid'] = $languageUid;
            $newDayRecord['l10n_parent'] = $l10nParent;

            if ($this->getBackendUser()->workspace > 0) {
                // Verify whether an active LIVE record exists to which a new relation can be established
                if (array_key_exists($newDayRecordKey, $existingLiveDayRecords)) {
                    $existingLiveDayRecord = $existingLiveDayRecords[$newDayRecordKey];
                    $newDayRecord['t3_origuid'] = (int)$existingLiveDayRecord['uid'];
                    $newDayRecord['t3ver_oid'] = (int)$existingLiveDayRecord['uid'];
                    $newDayRecord['t3ver_state'] = VersionState::DEFAULT_STATE->value;
                } else {
                    // There are more new day records than currently available in the LIVE workspace. Create new day
                    // records here without an associated relational record in the LIVE workspace.
                    $newDayRecord['t3_origuid'] = 0;
                    $newDayRecord['t3ver_oid'] = 0;
                    $newDayRecord['t3ver_state'] = VersionState::NEW_PLACEHOLDER->value;
                }
            }
        }

        return $newDayRecordKey;
    }

    protected function getExistingDayRecords(int $eventUid, int $workspace = null): array
    {
        $schema = $this->tcaSchemaFactory->get('tx_events2_domain_model_event');

        $relationHandler = $this->createRelationHandlerInstance($workspace);
        $relationHandler->initializeForField(
            'tx_events2_domain_model_event',
            $schema->getField('days'),
            $eventUid,
        );

        $dayRecords = [];
        foreach ($relationHandler->getValueArray() as $dayUid) {
            $dayRecords[] = BackendUtility::getRecord(self::TABLE, (int)$dayUid);
        }

        return $dayRecords;
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));

        return $queryBuilder;
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
