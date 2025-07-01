<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Service;

use JWeiland\Events2\Service\Record\DayRecordService;
use JWeiland\Events2\Service\Record\EventRecordService;
use JWeiland\Events2\Service\Record\ExceptionRecordService;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ReferenceIndex;

/**
 * While saving an event in the backend, this class generates all the day records
 * and sets them in relation to the event record.
 */
readonly class DayRelationService
{
    public function __construct(
        protected DayGeneratorService $dayGenerator,
        protected DayRecordService $dayRecordService,
        protected EventRecordService $eventRecordService,
        protected ExceptionRecordService $exceptionRecordService,
        protected ReferenceIndex $referenceIndex,
        protected LoggerInterface $logger,
    ) {}

    /**
     * Delete all related day records of a given event and
     * start re-creating the day records.
     */
    public function createDayRelations(int $eventUid): void
    {
        // Always operate on the LIVE version. If a record is required in a different workspace, handle it accordingly.
        $eventUid = BackendUtility::getLiveVersionIdOfRecord('tx_events2_domain_model_event', $eventUid) ?? $eventUid;

        // Early return if certain event columns are missing or if the event record is a translation.
        $eventRecordInDefaultLanguage = $this->getEventRecord($eventUid);
        if ($eventRecordInDefaultLanguage === [] || $this->shouldSkip($eventRecordInDefaultLanguage)) {
            return;
        }

        try {
            $this->dayRecordService->removeAllByEventUid($eventUid);

            $this->dayRecordService->bulkInsertAllDayRecords(
                $this->dayGenerator->getDateTimeStorageForEventRecord($eventRecordInDefaultLanguage)->getDayRecords(),
                $eventUid,
                $this->eventRecordService->getLanguageUidsOfTranslatedEventRecords($eventRecordInDefaultLanguage)
            );
        } catch (\Throwable $exception) {
            $this->logger->error(sprintf(
                'Error while building day records for event %d: %s',
                $eventUid,
                $exception->getMessage(),
            ));
        }
    }

    /**
     * All date-related columns are copied by the DataHandler during translation.
     * Do not create entries for translated event records.
     */
    private function shouldSkip(array $eventRecord): bool
    {
        if (!isset($eventRecord['uid'], $eventRecord['event_type'], $eventRecord['sys_language_uid'])) {
            $this->logger->error('Missing required columns [uid, event_type, sys_language_uid] in event record: ' . $eventRecord['uid'] ?? 0);
            return true;
        }

        return $eventRecord['uid'] === 0
            || $eventRecord['event_type'] === ''
            || $eventRecord['sys_language_uid'] > 0;
    }

    protected function getEventRecord(int $eventUid): array
    {
        $eventRecord = BackendUtility::getRecordWSOL('tx_events2_domain_model_event', $eventUid);
        if ($eventRecord === null) {
            $this->logger->warning('Event record could not be found: ' . $eventUid);
            return [];
        }

        $eventRecord['exceptions'] = $this->exceptionRecordService->findAllByEventUid($eventUid);

        return $eventRecord;
    }
}
