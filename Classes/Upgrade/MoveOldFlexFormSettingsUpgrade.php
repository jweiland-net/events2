<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Upgrade;

use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * With events2 8.0.0 we have moved some FlexForm Settings to another sheet.
 * To prevent duplicates in DB, this update wizard removes old settings from FlexForm.
 */
class MoveOldFlexFormSettingsUpgrade implements UpgradeWizardInterface
{
    public function getIdentifier(): string
    {
        return 'events2MoveFlexFormFields';
    }

    public function getTitle(): string
    {
        return '[events2] Move old FlexForm fields to new FlexForm sheet';
    }

    public function getDescription(): string
    {
        return 'It seems that some fields of FlexForm have not been updated yet. ' .
            'Please start this wizard to re-arrange the fields to their new location.';
    }

    public function updateNecessary(): bool
    {
        $records = $this->getTtContentRecordsWithEvents2Plugin();
        foreach ($records as $record) {
            $valueFromDatabase = (string)$record['pi_flexform'] !== '' ? GeneralUtility::xml2array($record['pi_flexform']) : [];
            if (!is_array($valueFromDatabase)) {
                continue;
            }

            if (empty($valueFromDatabase)) {
                continue;
            }

            if (!isset($valueFromDatabase['data'])) {
                continue;
            }

            if (!is_array($valueFromDatabase['data'])) {
                continue;
            }

            if (array_key_exists('sDEFAULT', $valueFromDatabase['data'])) {
                return true;
            }

            try {
                if (
                ArrayUtility::getValueByPath(
                    $valueFromDatabase,
                    'data/sDEF/lDEF/switchableControllerActions'
                )
                ) {
                    return true;
                }
            } catch (MissingArrayPathException $missingArrayPathException) {
                // If value does not exist, check further requirements
            }
        }

        return false;
    }

    public function executeUpdate(): bool
    {
        $records = $this->getTtContentRecordsWithEvents2Plugin();
        foreach ($records as $record) {
            $valueFromDatabase = (string)$record['pi_flexform'] !== '' ? GeneralUtility::xml2array($record['pi_flexform']) : [];
            if (!is_array($valueFromDatabase)) {
                continue;
            }

            if (empty($valueFromDatabase)) {
                continue;
            }

            $this->moveSheetDefaultToDef($valueFromDatabase);
            $ttContentListType = $this->migrateSwitchableControllerActions($valueFromDatabase, $record['list_type']);

            $connection = $this->getConnectionPool()->getConnectionForTable('tt_content');
            $connection->update(
                'tt_content',
                [
                    'list_type' => $ttContentListType,
                    'pi_flexform' => $this->checkValue_flexArray2Xml($valueFromDatabase)
                ],
                [
                    'uid' => (int)$record['uid']
                ],
                [
                    'pi_flexform' => \PDO::PARAM_STR
                ]
            );
        }

        return true;
    }

    /**
     * @return array<class-string<DatabaseUpdatedPrerequisite>>
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class
        ];
    }

    /**
     * Get all (incl. deleted/hidden) tt_content records with plugin maps2_maps2
     *
     * @return array
     */
    protected function getTtContentRecordsWithEvents2Plugin(): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();

        $statement = $queryBuilder
            ->select('uid', 'list_type', 'pi_flexform')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'CType',
                    $queryBuilder->createNamedParameter('list')
                ),
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq(
                        'list_type',
                        $queryBuilder->createNamedParameter('events2_events')
                    ),
                    $queryBuilder->expr()->eq(
                        'list_type',
                        $queryBuilder->createNamedParameter('events2_calendar')
                    ),
                    $queryBuilder->expr()->eq(
                        'list_type',
                        $queryBuilder->createNamedParameter('events2_search')
                    )
                )
            )
            ->execute();

        $records = [];
        while ($record = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $records[] = $record;
        }

        return $records;
    }

    /**
     * It's not a must-have, but sDEF seems to be more default than sDEFAULT as first sheet name in TYPO3
     */
    protected function moveSheetDefaultToDef(array &$valueFromDatabase): void
    {
        if (array_key_exists('sDEFAULT', $valueFromDatabase['data'])) {
            foreach ($valueFromDatabase['data']['sDEFAULT']['lDEF'] as $field => $value) {
                $this->moveFieldFromOldToNewSheet($valueFromDatabase, $field, 'sDEFAULT', 'sDEF');
            }

            // remove old sheet completely
            unset($valueFromDatabase['data']['sDEFAULT']);
        }
    }

    /**
     * Move field from one sheet to another and remove field from old location
     */
    protected function moveFieldFromOldToNewSheet(
        array &$valueFromDatabase,
        string $field,
        string $oldSheet,
        string $newSheet
    ): void {
        try {
            $value = ArrayUtility::getValueByPath(
                $valueFromDatabase,
                sprintf(
                    'data/%s/lDEF/%s',
                    $oldSheet,
                    $field
                )
            );

            // Create base sheet, if not exist
            if (!array_key_exists($newSheet, $valueFromDatabase['data'])) {
                $valueFromDatabase['data'][$newSheet] = [
                    'lDEF' => []
                ];
            }

            // Move field to new location, if not already done
            if (!array_key_exists($field, $valueFromDatabase['data'][$newSheet]['lDEF'])) {
                $valueFromDatabase['data'][$newSheet]['lDEF'][$field] = $value;
            }

            // Remove old reference
            unset($valueFromDatabase['data'][$oldSheet]['lDEF'][$field]);
        } catch (MissingArrayPathException $missingArrayPathException) {
            // Path does not exist in Array. Do not update anything
        }
    }

    protected function migrateSwitchableControllerActions(array &$valueFromDatabase, string $ttContentListType): string
    {
        try {
            $actions = ArrayUtility::getValueByPath(
                $valueFromDatabase,
                'data/sDEF/lDEF/switchableControllerActions/vDEF'
            );

            if ($ttContentListType === 'events2_events') {
                $ttContentListType = 'events2_list';
                switch ($actions) {
                    case 'Day->listLatest;Day->show;Day->showByTimestamp;Location->show;Video->show':
                        $listType = 'listLatest';
                        $ttContentListType = 'events2_list';
                        break;
                    case 'Day->listToday;Day->show;Day->showByTimestamp;Location->show;Video->show':
                        $listType = 'listToday';
                        $ttContentListType = 'events2_list';
                        break;
                    case 'Day->listThisWeek;Day->show;Day->showByTimestamp;Location->show;Video->show':
                        $listType = 'listWeek';
                        $ttContentListType = 'events2_list';
                        break;
                    case 'Day->listRange;Day->show;Day->showByTimestamp;Location->show;Video->show':
                        $listType = 'listRange';
                        $ttContentListType = 'events2_list';
                        break;
                    default:
                        $listType = 'list';
                }
                $valueFromDatabase['data']['sDEF']['lDEF']['settings.listType']['vDEF'] = $listType;
            } elseif ($ttContentListType === 'events2_search') {
                $ttContentListType = 'events2_searchform';
            }

            // Remove old reference
            unset($valueFromDatabase['data']['sDEF']['lDEF']['switchableControllerActions']);
        } catch (MissingArrayPathException $missingArrayPathException) {
            // Path does not exist in Array. Do not update anything
        }

        return $ttContentListType;
    }

    /**
     * Converts an array to FlexForm XML
     */
    protected function checkValue_flexArray2Xml(array $array): string
    {
        return GeneralUtility::makeInstance(FlexFormTools::class)
            ->flexArray2Xml($array, true);
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
