<?php

namespace JWeiland\Events2\ViewHelpers\Widget\Controller;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Time;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetController;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ShowEventDatesController extends AbstractWidgetController
{
    /**
     * @var \JWeiland\Events2\Utility\DateTimeUtility
     */
    protected $dateTimeUtility;

    /**
     * @var \JWeiland\Events2\Utility\EventUtility
     */
    protected $eventUtility;

    /**
     * @var \JWeiland\Events2\Domain\Model\Event
     */
    protected $event;

    /**
     * make exceptions of event global available for this class.
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    protected $exceptions = null;

    /**
     * inject DateTime Utility.
     *
     * @param \JWeiland\Events2\Utility\DateTimeUtility $dateTimeUtility
     */
    public function injectDateTimeUtility(\JWeiland\Events2\Utility\DateTimeUtility $dateTimeUtility)
    {
        $this->dateTimeUtility = $dateTimeUtility;
    }

    /**
     * inject Event Utility.
     *
     * @param \JWeiland\Events2\Utility\EventUtility $eventUtility
     */
    public function injectEventUtility(\JWeiland\Events2\Utility\EventUtility $eventUtility)
    {
        $this->eventUtility = $eventUtility;
    }

    /**
     * constructor of this class.
     */
    public function __construct()
    {
        parent::__construct();
        $this->exceptions = new ObjectStorage();
    }

    /**
     * setter for event.
     *
     * @param \JWeiland\Events2\Domain\Model\Event $event
     */
    public function setEvent($event)
    {
        $this->event = $event;
    }

    /**
     * getter for event.
     *
     * @return \JWeiland\Events2\Domain\Model\Event
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * setter for exceptions.
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $exceptions
     */
    public function setExceptions(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $exceptions)
    {
        $this->exceptions = $exceptions;
    }

    /**
     * getter for exceptions.
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getExceptions()
    {
        return $this->exceptions;
    }

    /**
     */
    public function initializeAction()
    {
        /** @var \JWeiland\Events2\Domain\Model\Event $event */
        $event = $this->widgetConfiguration['event'];
        $this->setExceptions($event->getExceptions());
        $this->setEvent($event);
    }

    /**
     * This index action creates a template where all days
     * are listed with their time records and exceptions.
     */
    public function indexAction()
    {
        $days = $this->sortDaysByDateAndTime(
            $this->getCollectedDays()
        );
        $this->view->assign('days', $days);
    }

    /**
     * get all days related to current event
     * already merged with all kinds of exceptions.
     *
     * @return array
     */
    public function getCollectedDays()
    {
        $days = array();
        $this->addFutureDaysFromEventRecord($days);

        // event->getDays already contains all Exceptions of type "Add"
        // above we have added all exceptions of type "Time"
        // while generating the day array we have added all exceptions of type "Info"
        // so only exceptions of type "Remove" are missing
        $this->addFutureDaysFromRemovedEventExceptions($days);

        return $days;
    }

    /**
     * add all visible and future days from event to days array.
     *
     * @param array $days
     * @retrun void days were added by reference
     */
    protected function addFutureDaysFromEventRecord(array &$days)
    {
        $today = $this->dateTimeUtility->standardizeDateTimeObject(new \DateTime());

        // as long as I can not change query building for sub-models in extbase (repository)
        // I have to reduce days with help of PHP. Maybe it will work with TYPO3 7.0

        /** @var \JWeiland\Events2\Domain\Model\Day $day */
        foreach ($this->event->getDays() as $day) {
            // only add days of today and in future
            if ($day->getDay() >= $today) {
                // some days can start multiple times each day
                $times = $this->eventUtility->getTimesForDay($this->event, $day);
                if ($times->count()) {
                    foreach ($times as $time) {
                        $days[] = $this->buildDayArray($day, $time);
                    }
                } else {
                    // there can also be events without a related time record
                    // in that case we add the day with an empty time record
                    $time = new Time();
                    $days[] = $this->buildDayArray($day, $time);
                }
            }
        }
    }

    /**
     * We don't want to add removed days to calender, but in detail view we
     * want to show them. So we need them here to add a special CSS-Class in template.
     *
     * @param array $days
     */
    protected function addFutureDaysFromRemovedEventExceptions(array &$days)
    {
        $today = $this->dateTimeUtility->standardizeDateTimeObject(new \DateTime());

        // get all Exceptions of type "Remove" regardless of day
        $removedExceptions = new \SplObjectStorage();
        /** @var \JWeiland\Events2\Domain\Model\Exception $exception */
        foreach ($this->getExceptions() as $exception) {
            if ($exception->getExceptionType() === 'Remove') {
                $removedExceptions->attach($exception);
            }
        }

        //
        if ($removedExceptions->count()) {
            /** @var \JWeiland\Events2\Domain\Model\Exception $removedException */
            foreach ($removedExceptions as $removedException) {
                // only add days of today and in future
                if ($removedException->getExceptionDate() >= $today) {
                    // Exceptions does not have a relation to day domain model. So we create a temporary one
                    $day = new Day();
                    $day->setDay($removedException->getExceptionDate());

                    // some days can start multiple times each day
                    $times = $this->eventUtility->getTimesForDay($this->event, $day);
                    if ($times->count()) {
                        foreach ($times as $time) {
                            $days[] = $this->buildDayArray($day, $time);
                        }
                    } else {
                        // there can also be events without a related time record
                        // in that case we add the day with an empty time record
                        $time = new Time();
                        $days[] = $this->buildDayArray($day, $time);
                    }
                }
            }
        }
    }

    /**
     * build day array
     * this must be an array, because we will sort with array_multisort later.
     *
     * @param \JWeiland\Events2\Domain\Model\Day  $day
     * @param \JWeiland\Events2\Domain\Model\Time $time
     *
     * @return array
     */
    public function buildDayArray(\JWeiland\Events2\Domain\Model\Day $day, \JWeiland\Events2\Domain\Model\Time $time)
    {
        $dayArray = array();

        // add original day and time object
        $dayArray['day'] = $day;
        $dayArray['time'] = $time;

        // add event date as timestamp (for sorting)
        $eventDate = $this->dateTimeUtility->standardizeDateTimeObject($day->getDay());
        $dayArray['eventDate'] = $eventDate->format('U');

        // add event time as string (for sorting)
        $dayArray['eventTime'] = $time->getTimeBegin();

        // add flag to mark removed days
        $removedExceptions = $this->eventUtility->getExceptionsForDay($this->event, $day, 'remove');
        if ($removedExceptions->count()) {
            $dayArray['isRemoved'] = true;
        } else {
            $dayArray['isRemoved'] = false;
        }

        // add exceptions
        $dayArray['infos'] = $this->eventUtility->getExceptionsForDay($this->event, $day);

        return $dayArray;
    }

    /**
     * multisort days by date and time.
     *
     * @param array $days
     *
     * @return array
     */
    public function sortDaysByDateAndTime(array $days)
    {
        if (count($days)) {
            $eventDate = array();
            $eventTime = array();
            foreach ($days as $key => $day) {
                $eventDate[$key] = $day['eventDate']; // integer
                $eventTime[$key] = $day['EventTime']; // string
            }
            array_multisort($eventDate, SORT_ASC, SORT_NUMERIC, $eventTime, SORT_ASC, SORT_STRING, $days);
        }

        return $days;
    }
}
