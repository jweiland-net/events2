<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Factory;

use JWeiland\Events2\Domain\Repository\CategoryRepository;
use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Domain\Repository\ExceptionRepository;
use JWeiland\Events2\Domain\Repository\LocationRepository;
use JWeiland\Events2\Domain\Repository\OrganizerRepository;
use JWeiland\Events2\Helper\DateTimeHelper;

/*
 * If you work with a lot of events it may be faster to work with plain arrays instead of all these
 * domain modelling of extbase.
 * Use this factory to build your own interpretation of event record (with/without location/organizer/... records)
 */
class EventFactory
{
    /**
     * @var EventRepository
     */
    protected $eventRepository;

    /**
     * @var DayRepository
     */
    protected $dayRepository;

    /**
     * @var OrganizerRepository
     */
    protected $organizerRepository;

    /**
     * @var LocationRepository
     */
    protected $locationRepository;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var ExceptionRepository
     */
    protected $exceptionRepository;

    /**
     * @var DateTimeHelper
     */
    protected $dateTimeHelper;

    public function __construct(
        EventRepository $eventRepository,
        DayRepository $dayRepository,
        OrganizerRepository $organizerRepository,
        LocationRepository $locationRepository,
        CategoryRepository $categoryRepository,
        ExceptionRepository $exceptionRepository,
        DateTimeHelper $dateTimeHelper
    ) {
        $this->eventRepository = $eventRepository;
        $this->dayRepository = $dayRepository;
        $this->organizerRepository = $organizerRepository;
        $this->locationRepository = $locationRepository;
        $this->categoryRepository = $categoryRepository;
        $this->exceptionRepository = $exceptionRepository;
        $this->dateTimeHelper = $dateTimeHelper;
    }

    public function getEventRecord(
        int $eventUid,
        bool $ignoreEnableFields = false,
        bool $addDateTimeObjects = false,
        bool $includeDayRecords = false,
        bool $includeOrganizers = false,
        bool $includeLocation = false,
        bool $includeCategories = false,
        bool $includeExceptions = false
    ): array {
        $eventRecord = $this->eventRepository->getEventRecord($eventUid, $ignoreEnableFields);
        if ($eventRecord === []) {
            return [];
        }

        if ($addDateTimeObjects) {
            $this->dateTimeHelper->addDateTimeObjects(
                $eventRecord,
                'tx_events2_domain_model_event'
            );
        }

        if ($includeDayRecords) {
            $this->addDayRecords($eventRecord, $ignoreEnableFields, $addDateTimeObjects);
        }
        if ($includeOrganizers) {
            $this->addOrganizers($eventRecord, $ignoreEnableFields, $addDateTimeObjects);
        }
        if ($includeLocation) {
            $this->addLocation($eventRecord, $ignoreEnableFields, $addDateTimeObjects);
        }
        if ($includeCategories) {
            $this->addCategories($eventRecord, $ignoreEnableFields, $addDateTimeObjects);
        }
        if ($includeExceptions) {
            $this->addExceptions($eventRecord, $ignoreEnableFields, $addDateTimeObjects);
        }

        return $eventRecord;
    }

    protected function addDayRecords(array &$eventRecord, bool $ignoreEnableFields, bool $addDateTimeObjects): void
    {
        $this->dayRepository->addDayRecords($eventRecord, $ignoreEnableFields);
        if ($addDateTimeObjects && is_array($eventRecord['days'])) {
            foreach ($eventRecord['days'] as &$dayRecord) {
                $this->dateTimeHelper->addDateTimeObjects(
                    $dayRecord,
                    'tx_events2_domain_model_day'
                );
            }
        }
    }

    protected function addOrganizers(array &$eventRecord, bool $ignoreEnableFields, bool $addDateTimeObjects): void
    {
        $this->organizerRepository->addOrganizers($eventRecord, $ignoreEnableFields);
        if ($addDateTimeObjects && is_array($eventRecord['organizers'])) {
            foreach ($eventRecord['organizers'] as &$organizerRecord) {
                $this->dateTimeHelper->addDateTimeObjects(
                    $organizerRecord,
                    'tx_events2_domain_model_organizer'
                );
            }
        }
    }

    protected function addLocation(array &$eventRecord, bool $ignoreEnableFields, bool $addDateTimeObjects): void
    {
        $this->locationRepository->addLocation($eventRecord, $ignoreEnableFields);
        if ($addDateTimeObjects && is_array($eventRecord['location'])) {
            $this->dateTimeHelper->addDateTimeObjects(
                $eventRecord['location'],
                'tx_events2_domain_model_location'
            );
        }
    }

    protected function addCategories(array &$eventRecord, bool $ignoreEnableFields, bool $addDateTimeObjects): void
    {
        $this->categoryRepository->addCategories($eventRecord, $ignoreEnableFields);
        if ($addDateTimeObjects && is_array($eventRecord['categories'])) {
            foreach ($eventRecord['categories'] as &$categoryRecord) {
                $this->dateTimeHelper->addDateTimeObjects(
                    $categoryRecord,
                    'sys_category'
                );
            }
        }
    }

    protected function addExceptions(array &$eventRecord, bool $ignoreEnableFields, bool $addDateTimeObjects): void
    {
        $this->exceptionRepository->addExceptions($eventRecord, $ignoreEnableFields);
        if ($addDateTimeObjects && is_array($eventRecord['exceptions'])) {
            foreach ($eventRecord['exceptions'] as &$exceptionRecord) {
                $this->dateTimeHelper->addDateTimeObjects(
                    $exceptionRecord,
                    'tx_events2_domain_model_exception'
                );
            }
        }
    }
}
