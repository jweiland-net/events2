<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Importer;

use JWeiland\Events2\Domain\Model\Event;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Extbase\Persistence\Generic\LoadingStrategyInterface;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Builds the event record (database column names and raw values) of an
 * event model, as the DataHandler would see it. Used for path segment (slug)
 * generation, so that TCA "generatorOptions" (fields, postModifiers) see the
 * same data as in the backend.
 *
 * Only values which are already in memory are used. No database query.
 * Inline and file relations (times, exceptions, links, images, days) are not
 * part of the record: the importer creates these children without UID.
 */
readonly class EventRecordBuilder
{
    private const TABLE = 'tx_events2_domain_model_event';

    /**
     * Formats of native date columns (TCA "dbType") like in DataHandler
     */
    private const NATIVE_DATE_FORMATS = [
        'date' => 'Y-m-d',
        'datetime' => 'Y-m-d H:i:s',
    ];

    public function __construct(
        private TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    /**
     * A new event (without UID) gets a record without "uid".
     *
     * @return array<string, int|string|null>
     */
    public function buildRecord(Event $event): array
    {
        $record = array_replace(
            $this->getSystemColumns($event),
            $this->getScalarColumns($event),
            $this->getDateColumns($event),
            $this->getRelationColumns($event),
        );

        $uid = (int)$event->getUid();
        if ($uid > 0) {
            $record['uid'] = $uid;
        }

        return $record;
    }

    /**
     * @return array<string, int>
     */
    private function getSystemColumns(Event $event): array
    {
        return [
            'pid' => (int)$event->getPid(),
            'sys_language_uid' => (int)$event->getSysLanguageUid(),
            'l10n_parent' => $event->getL10nParent(),
            'hidden' => (int)$event->getHidden(),
        ];
    }

    /**
     * @return array<string, int|string>
     */
    private function getScalarColumns(Event $event): array
    {
        return [
            'event_type' => $event->getEventType(),
            'top_of_list' => (int)$event->getTopOfList(),
            'title' => $event->getTitle(),
            'path_segment' => $event->getPathSegment(),
            'same_day' => (int)$event->getSameDay(),
            'xth' => $event->getXth(),
            'weekday' => $event->getWeekday(),
            'each_weeks' => $event->getEachWeeks(),
            'each_months' => $event->getEachMonths(),
            'teaser' => $event->getTeaser(),
            'detail_information' => $event->getDetailInformation(),
            'attendance_mode' => $event->getAttendanceMode(),
            'free_entry' => (int)$event->getFreeEntry(),
            'import_id' => $event->getImportId(),
        ];
    }

    /**
     * @return array<string, int|string|null>
     */
    private function getDateColumns(Event $event): array
    {
        return [
            'starttime' => $this->getDateValue('starttime', $event->getStarttime()),
            'endtime' => $this->getDateValue('endtime', $event->getEndtime()),
            'event_begin' => $this->getDateValue('event_begin', $event->getEventBegin()),
            'event_end' => $this->getDateValue('event_end', $event->getEventEnd()),
            'recurring_end' => $this->getDateValue('recurring_end', $event->getRecurringEnd()),
        ];
    }

    /**
     * Like DataHandler: a native date column (TCA "dbType") contains the
     * formatted date in server time, all other "datetime" columns contain
     * a timestamp.
     */
    private function getDateValue(string $column, ?\DateTimeImmutable $date): int|string|null
    {
        $format = self::NATIVE_DATE_FORMATS[$this->getDbType($column)] ?? null;
        if ($format === null) {
            return $date?->getTimestamp() ?? 0;
        }

        return $date?->setTimezone(new \DateTimeZone(date_default_timezone_get()))->format($format);
    }

    private function getDbType(string $column): string
    {
        $schema = $this->tcaSchemaFactory->get(self::TABLE);
        if (!$schema->hasField($column)) {
            return '';
        }

        return (string)($schema->getField($column)->getConfiguration()['dbType'] ?? '');
    }

    /**
     * Relations as in the data of a DataHandler call: UID of the location,
     * comma-separated UIDs of organizers and categories. Not yet loaded
     * (lazy) relations are skipped, as loading them would query the database.
     *
     * @return array<string, int|string>
     */
    private function getRelationColumns(Event $event): array
    {
        $record = [
            'location' => (int)$event->getLocation()?->getUid(),
        ];

        $relations = [
            'organizers' => $event->getOrganizers(),
            'categories' => $event->getCategories(),
        ];
        foreach ($relations as $column => $objectStorage) {
            if (!$objectStorage instanceof LoadingStrategyInterface) {
                $record[$column] = $this->getUidList($objectStorage);
            }
        }

        return $record;
    }

    /**
     * Not yet persisted objects have no UID and are skipped
     */
    private function getUidList(ObjectStorage $objectStorage): string
    {
        $uids = [];
        foreach ($objectStorage as $domainObject) {
            $uid = (int)$domainObject->getUid();
            if ($uid > 0) {
                $uids[] = $uid;
            }
        }

        return implode(',', $uids);
    }
}
