<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Service;

use JWeiland\Events2\Service\Record\EventRecordService;
use JWeiland\Events2\Service\Record\ExceptionRecordService;
use JWeiland\Events2\Service\Record\DayRecordService;
use JWeiland\Events2\Utility\DateTimeUtility;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DefaultRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
        protected TimeService $timeService,
        protected DateTimeUtility $dateTimeUtility,
        protected ConnectionPool $connectionPool,
        protected LoggerInterface $logger,
    ) {}

    /**
     * Delete all related day records of a given event and
     * start re-creating the day records.
     */
    public function createDayRelations(int $eventUid): array
    {
        $eventRecord = $this->getEventRecord($eventUid);
        if ($eventRecord === [] || $this->shouldSkip($eventRecord)) {
            return $eventRecord;
        }

        try {
            $this->dayRecordService->removeAllByEventRecord($eventRecord);
            $dayRecords = $this->dayGenerator->getDateTimeStorageForEventRecord($eventRecord)->getDayRecords();
            $this->dayRecordService->bulkInsertAllDayRecords($dayRecords);
            $eventRecord['days'] = $dayRecords;
        } catch (\Throwable $exception) {
            $this->logger->error(sprintf(
                'Error while building day records for event %d: %s',
                $eventUid,
                $exception->getMessage()
            ));
        }

        return $eventRecord;
    }

    private function shouldSkip(array $eventRecord): bool
    {
        return $eventRecord['uid'] === 0
            || $eventRecord['event_type'] === ''
            || $eventRecord['sys_language_uid'] > 0;
    }


    protected function getEventRecord(int $eventUid): array
    {
        /** @var DefaultRestrictionContainer $restriction */
        $restriction = GeneralUtility::makeInstance(DefaultRestrictionContainer::class);
        $restriction->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $eventRecord = $this->eventRecordService->findByUid($eventUid, true, $restriction);
        BackendUtility::workspaceOL('tx_events2_domain_model_event', $eventRecord);

        if ($eventRecord === null) {
            $this->logger->warning('Event record can not be overlayed into current workspace: ' . $eventUid);
            return [];
        }

        $eventRecord['exceptions'] = $this->exceptionRecordService->getAllByEventRecord($eventRecord);
        $eventRecord['days'] = [];

        return $eventRecord;
    }
}
