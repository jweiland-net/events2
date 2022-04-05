<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Service;

use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Event\PostGenerateDaysEvent;
use JWeiland\Events2\Tca\BitMask\WeekDayBitMask;
use JWeiland\Events2\Tca\BitMask\XthBitMask;
use JWeiland\Events2\Utility\DateTimeUtility;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * Class to generate all day records for an event within a configured range (ExtensionManager).
 * It does now respect time information, just days. The time records will be processed later.
 */
class DayGeneratorService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected array $dateTimeStorage = [];

    protected ExtConf $extConf;

    protected DateTimeUtility $dateTimeUtility;

    protected EventDispatcher $eventDispatcher;

    public function __construct(
        EventDispatcher $eventDispatcher,
        ExtConf $extConf,
        DateTimeUtility $dateTimeUtility
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->extConf = $extConf;
        $this->dateTimeUtility = $dateTimeUtility;
    }

    public function getDateTimeStorageForEvent(array $eventRecord): array
    {
        $this->dateTimeStorage = [];

        try {
            $this->addDateTimeObjectsToRecord($eventRecord, 'tx_events2_domain_model_event');
            $this->checkEventRecord($eventRecord);

            switch ($eventRecord['event_type']) {
                case 'recurring':
                    if ($eventRecord['each_weeks'] !== 0 || $eventRecord['each_months'] !== 0) {
                        $this->addDaysForWeeklyAndMonthlyEvent($eventRecord);
                    } else {
                        $this->addDaysForRecurringEvent($eventRecord);
                    }
                    $this->addEventExceptions($eventRecord);
                    break;
                case 'duration':
                    $this->addDaysForDurationalEvent($eventRecord);
                    $this->addEventExceptions($eventRecord);
                    break;
                case 'single':
                    $this->addDateTimeToStorage($eventRecord['event_begin']);
                    break;
                default:
            }

            $this->eventDispatcher->dispatch(
                new PostGenerateDaysEvent($eventRecord)
            );
        } catch (\Exception $exception) {
            $this->logger->error(sprintf(
                'Error occurred while building DateTime objects in DayGeneratorService at line %d: %s: %d',
                $exception->getLine(),
                $exception->getMessage(),
                $exception->getCode()
            ));

            return [];
        }

        ksort($this->dateTimeStorage);

        return $this->dateTimeStorage;
    }

    /**
     * @throws \Exception
     */
    protected function addDateTimeObjectsToRecord(array &$record, string $table): void
    {
        foreach ($record as $column => $value) {
            if (!isset($GLOBALS['TCA'][$table]['columns'][$column])) {
                continue;
            }

            if (!isset($GLOBALS['TCA'][$table]['columns'][$column]['config']['renderType'])) {
                continue;
            }

            if ($GLOBALS['TCA'][$table]['columns'][$column]['config']['renderType'] === 'inputDateTime') {
                $record[$column] = $this->dateTimeUtility->convert($value);
            }
        }

        if ($table === 'tx_events2_domain_model_event') {
            foreach ($record['exceptions'] as &$exceptionRecord) {
                $this->addDateTimeObjectsToRecord(
                    $exceptionRecord,
                    'tx_events2_domain_model_exception'
                );
            }
        }
    }

    /**
     * Check, if event record contains all needed properties to process.
     *
     * @throws \Exception
     */
    protected function checkEventRecord(array $eventRecord): void
    {
        $neededProperties = [
            'event_type',
            'event_begin',
            'event_end',
            'recurring_end',
            'each_weeks',
            'each_months',
            'xth',
            'weekday',
            'recurring_end',
            'exceptions'
        ];

        foreach ($neededProperties as $neededProperty) {
            if (!array_key_exists($neededProperty, $eventRecord)) {
                throw new \Exception(sprintf(
                    'Invalid event record. It does not contain mandatory property: %s.',
                    $neededProperty
                ), 1649074484);
            }
        }

        if ($eventRecord['event_type'] === '') {
            throw new \Exception(
                'Invalid event record: Column "event_type" can not be empty.',
                1649074506
            );
        }

        if (
            $eventRecord['event_type'] === 'duration'
            && !$eventRecord['event_end'] instanceof \DateTimeImmutable
        ) {
            throw new \Exception(
                'Invalid event record: If event_type is set to "duration" column "event_end" has to be set.',
                1649074514
            );
        }

        if (!$eventRecord['event_begin'] instanceof \DateTimeImmutable) {
            throw new \Exception(
                'Invalid event record: Column "event_begin" can not be empty.',
                1649074525
            );
        }

        foreach ($eventRecord['exceptions'] as $exceptionRecord) {
            if (!$exceptionRecord['exception_date'] instanceof \DateTimeImmutable) {
                throw new \Exception(
                    'Invalid exception record: Column "exception_date" can not be empty.',
                    1649139356
                );
            }
        }

        // As long as these values will be generated by DateTimeUtility the Exceptions should never be called
        if ($eventRecord['event_begin']->format('H:i:s') !== '00:00:00') {
            throw new \Exception('Column "event_begin" was given with a time other than 00:00:00 which is not allowed.');
        }
        if (
            $eventRecord['event_end'] instanceof \DateTimeImmutable
            && $eventRecord['event_end']->format('H:i:s') !== '00:00:00'
        ) {
            throw new \Exception('Column "event_end" was given with a time other than 00:00:00 which is not allowed.');
        }
        if (
            $eventRecord['recurring_end'] instanceof \DateTimeImmutable
            && $eventRecord['recurring_end']->format('H:i:s') !== '00:00:00'
        ) {
            throw new \Exception('Column "recurring_end" was given with a time other than 00:00:00 which is not allowed.');
        }
    }

    /**
     * @throws \Exception
     */
    protected function addDaysForRecurringEvent(array $eventRecord): void
    {
        $dateToStopCalculatingTo = $this->getEndDateForCalculation($eventRecord);
        $firstDayOfMonth = $this->resetDateTimeToFirstDayOfMonth(
            $this->getStartDateForCalculation($eventRecord)
        );

        while ($firstDayOfMonth <= $dateToStopCalculatingTo) {
            $this->addDaysForMonth(
                $firstDayOfMonth->format('F'),
                (int)$firstDayOfMonth->format('Y'),
                $eventRecord
            );
            $firstDayOfMonth = $this->modifyDateTime($firstDayOfMonth, 'next month');
        }
    }

    /**
     * @throws \Exception
     */
    protected function addDaysForDurationalEvent(array $eventRecord): void
    {
        $dateToStartCalculatingFrom = $this->getStartDateForCalculation($eventRecord);
        $dateToStopCalculatingTo = $this->getEndDateForCalculation($eventRecord);

        while ($dateToStartCalculatingFrom <= $dateToStopCalculatingTo) {
            $this->addDateTimeToStorage($dateToStartCalculatingFrom);
            $dateToStartCalculatingFrom = $dateToStartCalculatingFrom->modify('+1 day');
        }
    }

    /**
     * @throws \Exception
     */
    protected function addDaysForWeeklyAndMonthlyEvent(array $eventRecord): void
    {
        $dateToStartCalculatingFrom = $this->getStartDateForCalculation($eventRecord);
        $dateToStopCalculatingTo = $this->getEndDateForCalculation($eventRecord);

        while ($dateToStartCalculatingFrom <= $dateToStopCalculatingTo) {
            $this->addDateTimeToStorage($dateToStartCalculatingFrom);
            $dateToStartCalculatingFrom = $dateToStartCalculatingFrom->modify(
                '+' . $eventRecord['each_months'] . ' months'
            );
            $dateToStartCalculatingFrom = $dateToStartCalculatingFrom->modify(
                '+' . $eventRecord['each_weeks'] . ' weeks'
            );
        }
    }

    /**
     * @throws \Exception
     */
    protected function addDaysForMonth(string $month, int $year, array $eventRecord): void
    {
        $dynamicDateTimeAtMidnight = $this->getDateTimeForToday();
        $lastDayOfMonth = $this->getLastDateTimeOfMonthAndYear($month, $year);
        $dateToStartCalculatingFrom = $this->getStartDateForCalculation($eventRecord);
        $dateToStopCalculatingTo = $this->getEndDateForCalculation($eventRecord);

        $xthBitMask = GeneralUtility::makeInstance(XthBitMask::class, $eventRecord['xth']);
        $weekDayBitMask = GeneralUtility::makeInstance(WeekDayBitMask::class, $eventRecord['weekday']);

        foreach ($xthBitMask->getSelectedWeeks() as $xthIndex => $xth) {
            foreach ($weekDayBitMask->getSelectedWeekdays() as $weekdayIndex => $weekday) {
                // Example: 'second wednesday of March 2013'
                $modifyString = $xthIndex . ' ' . $weekdayIndex . ' of ' . $month . ' ' . $year;
                $dynamicDateTimeAtMidnight = $this->modifyDateTime($dynamicDateTimeAtMidnight, $modifyString);

                if (
                    $dynamicDateTimeAtMidnight >= $dateToStartCalculatingFrom
                    && $dynamicDateTimeAtMidnight < $lastDayOfMonth
                    && $dynamicDateTimeAtMidnight <= $dateToStopCalculatingTo
                ) {
                    $this->addDateTimeToStorage($dynamicDateTimeAtMidnight);
                }
            }
        }
    }

    /**
     * @throws \Exception
     */
    protected function getLastDateTimeOfMonthAndYear(string $month, int $year): \DateTimeImmutable
    {
        // Do not reset time to midnight, as we want to keep given time.
        return $this->createDateTime(
            'last day of ' . $month . ' ' . $year . ' 23:59:59',
            false
        );
    }

    /**
     * Keep the month of given DateTime, but reset day of month to first of month.
     * 15.03.2020 => 01.03.2020
     *
     * @throws \Exception
     */
    protected function resetDateTimeToFirstDayOfMonth(\DateTimeImmutable $date): \DateTimeImmutable
    {
        return $this->modifyDateTime(
            $date,
            'first day of this month'
        );
    }

    /**
     * @throws \Exception
     */
    protected function createDateTime(string $modifier, bool $resetToMidnight = true): \DateTimeImmutable
    {
        try {
            $newDateTime = new \DateTimeImmutable($modifier);
            $newDateTime = $newDateTime->setTimezone(new \DateTimeZone(date_default_timezone_get()));

            if ($resetToMidnight) {
                $newDateTime = $this->dateTimeUtility->standardizeDateTimeObject($newDateTime);
            }
        } catch (\Exception $exception) {
            throw new \Exception(sprintf(
                'Creation of new DateTime object with modifier "%s" failed.',
                $modifier
            ), 1649066132);
        }

        return $newDateTime;
    }

    /**
     * @throws \Exception
     */
    protected function modifyDateTime(\DateTimeImmutable $dateTime, string $modifier): \DateTimeImmutable
    {
        try {
            $modifiedDateTime = $dateTime->modify($modifier);
        } catch (\Exception $exception) {
            throw new \Exception(sprintf(
                'Given DateTime object could not be modified with modifier "%s"',
                $modifier
            ), 1649066006);
        }

        return $modifiedDateTime;
    }

    /**
     * Returns DateTime object for today midnight
     *
     * @throws \Exception
     */
    protected function getDateTimeForToday(): \DateTimeImmutable
    {
        return $this->createDateTime('today');
    }

    /**
     * Returns current date subtracted with configured "recurring past" configuration in ExtensionManager.
     * If calculated date is older than eventBegin, it does not make sense to use that date, and we
     * will return eventBegin instead.
     *
     * @throws \Exception
     */
    protected function getStartDateForCalculation(array $eventRecord): \DateTimeImmutable
    {
        $eventBegin = $eventRecord['event_begin'];
        $dateToStartCalculatingFrom = $this->getEarliestDateForTimeFrame($eventBegin);

        // In case of eachWeeks and eachMonth $dateToStartCalculatingFrom has to be
        // exactly in sync with eventBegin
        if ($eventRecord['each_weeks'] !== 0 || $eventRecord['each_months'] !== 0) {
            while ($eventBegin < $dateToStartCalculatingFrom) {
                $eventBegin = $eventBegin->modify('+' . $eventRecord['each_months'] . ' months');
                $eventBegin = $eventBegin->modify('+' . $eventRecord['each_weeks'] . ' weeks');
            }

            $dateToStartCalculatingFrom = $eventBegin;
        }

        return $this->dateTimeUtility->standardizeDateTimeObject($dateToStartCalculatingFrom);
    }

    /**
     * This method creates a DateTime object based on recurring_past configuration of Extension Settings.
     * It returns the date which is more current.
     *
     * @throws \Exception
     */
    protected function getEarliestDateForTimeFrame(\DateTimeImmutable $dateForComparison): \DateTimeImmutable
    {
        $earliestDateOfTimeFrame = $this->createDateTime(sprintf(
            '-%d months',
            $this->extConf->getRecurringPast()
        ));

        if ($earliestDateOfTimeFrame > $dateForComparison) {
            return $earliestDateOfTimeFrame;
        }

        return $dateForComparison;
    }

    /**
     * Returns current date added with configured "recurring future" configuration in ExtensionManager.
     * If calculated date is older than eventEnd, it does not make sense to use that date, and we
     * will return eventEnd instead.
     *
     * @throws \Exception
     */
    protected function getEndDateForCalculation(array $eventRecord): \DateTimeImmutable
    {
        $today = $this->createDateTime('today');
        $eventBegin = $eventRecord['event_begin'];
        $latestDateOfTimeFrame = $this->modifyDateTime(
            $today > $eventBegin ? $today : $eventBegin,
            sprintf(
                '+%d months 23:59:59',
                $this->extConf->getRecurringFuture()
            )
        );

        /** @var ?\DateTimeImmutable $latestEventDate recurring_end can be null! */
        $latestEventDate = $eventRecord['event_type'] === 'duration'
            ? $eventRecord['event_end']
            : $eventRecord['recurring_end'];

        return $latestEventDate < $latestDateOfTimeFrame ? $latestEventDate : $latestDateOfTimeFrame;
    }

    protected function addDateTimeToStorage(\DateTimeImmutable $dateTime): void
    {
        // To prevent adding multiple day records for ONE day we set them all to midnight 00:00:00
        $dateTime = $this->dateTimeUtility->standardizeDateTimeObject($dateTime);

        // group days to make them unique
        $this->dateTimeStorage[$dateTime->format('U')] = $dateTime;
    }

    protected function removeDayFromStorage(\DateTimeImmutable $day): void
    {
        unset($this->dateTimeStorage[$day->format('U')]);
    }

    /**
     * @throws \Exception
     */
    protected function addEventExceptions(array $eventRecord): void
    {
        if (!$this->hasEventExceptions($eventRecord)) {
            return;
        }

        foreach ($eventRecord['exceptions'] as $exceptionRecord) {
            switch ($exceptionRecord['exception_type']) {
                case 'Add':
                    $this->addEventException($eventRecord, $exceptionRecord);
                    break;
                case 'Remove':
                    $this->removeDayFromStorage($exceptionRecord['exception_date']);
                    break;
                case 'Info':
                case 'Time':
                    break;
                default:
                    throw new \Exception(
                        sprintf(
                            'Type "%s" is no valid exception type',
                            $exceptionRecord['exception_type']
                        ),
                        1370003254
                    );
            }
        }
    }

    /**
     * @throws \Exception
     */
    protected function addEventException(array $eventRecord, array $exceptionRecord): void
    {
        $dateToStartCalculatingFrom = $this->getStartDateForCalculation($eventRecord);
        $dateToStopCalculatingTo = $this->getEndDateForCalculation($eventRecord);
        $exceptionDate = $exceptionRecord['exception_date'];

        if ($exceptionDate < $dateToStartCalculatingFrom) {
            return;
        }

        if ($exceptionDate > $dateToStopCalculatingTo) {
            return;
        }

        if ($exceptionDate instanceof \DateTimeImmutable) {
            $this->addDateTimeToStorage($exceptionDate);
        }
    }

    protected function hasEventExceptions(array $eventRecord): bool
    {
        return is_array($eventRecord['exceptions'])
            && $eventRecord['exceptions'] !== []
            && in_array($eventRecord['event_type'], ['recurring', 'duration']);
    }
}
