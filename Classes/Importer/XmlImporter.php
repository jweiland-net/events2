<?php

namespace JWeiland\Events2\Importer;

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
use JWeiland\Events2\Domain\Model\Category;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Exception;
use JWeiland\Events2\Domain\Model\Link;
use JWeiland\Events2\Domain\Model\Organizer;
use JWeiland\Events2\Domain\Model\Time;
use JWeiland\Events2\Task\Import;
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class XmlImporter extends AbstractImporter
{
    /**
     * @var array
     */
    protected $allowedMimeType = array(
        'application/xml'
    );

    /**
     * Import XML file
     *
     * @param FileInterface $file
     * @param AbstractTask $task
     *
     * @return bool
     */
    public function import(FileInterface $file, AbstractTask $task)
    {
        $this->initialize();
        if (!$this->validateXml($file)) {
            return false;
        }
        $events = GeneralUtility::xml2array($file->getContents());

        $runSuccessful = true;
        $amountOfValidEvents = 0;
        foreach ($events as $event) {
            if ($this->isValidEvent($event)) {
                $this->saveEvent(
                    $this->createEvent($event),
                    $task
                );
                $amountOfValidEvents++;
            } else {
                $runSuccessful = false;
                break;
            }
        }
        $this->getPersistenceManager()->persistAll();
        $this->addMessage('We have imported ' . $amountOfValidEvents . ' valid events');

        return $runSuccessful;
    }

    /**
     * Validate XML for import
     *
     * @param FileInterface $file
     *
     * @return bool
     */
    protected function validateXml(FileInterface $file)
    {
        try {
            $domDocument = new \DOMDocument();
            $domDocument->loadXML($file->getContents());
            $domDocument->schemaValidate(PATH_site . 'typo3conf/ext/events2/Resources/Public/XmlImportValidator.xsd');
        } catch (\Exception $e) {
            $this->addMessage('XML does not comply with XmlImportValidator.xml.', FlashMessage::ERROR);
            $this->addMessage($e->getMessage(), FlashMessage::ERROR);
            return false;
        }
        return true;
    }

    /**
     * Is valid event data
     *
     * @param array $event
     *
     * @return bool
     */
    protected function isValidEvent(array $event)
    {
        $eventBegin = \DateTime::createFromFormat('Y-m-d', $event['event_begin']);
        if ($eventBegin < $this->today) {
            $this->addMessage('event_begin can not be in past', FlashMessage::ERROR);
            return false;
        }

        return true;
    }

    /**
     * Create new event
     *
     * @param array $data
     *
     * @return Event
     */
    protected function createEvent(array $data)
    {
        /** @var Event $event */
        $event = $this->objectManager->get('JWeiland\\Events2\\Domain\\Model\\Event');
        $this->addRootProperties($event, $data);
        $this->addDateProperties($event, $data);
        $this->addTimeProperties($event, $data);
        $this->addOrganizer($event, $data);
        $this->addLocation($event, $data);
        $this->addLinks($event, $data);
        $this->addExceptions($event, $data);
        $this->addCategories($event, $data);

        return $event;
    }

    /**
     * Add root properties
     *
     * @param Event $event
     * @param array $data
     *
     * @return void
     */
    protected function addRootProperties(Event $event, array $data)
    {
        $allowedRootProperties = array(
            'event_type',
            'top_of_list',
            'title',
            'teaser',
            'same_day',
            'xth',
            'weekday',
            'each_weeks',
            'detail_informations',
            'free_entry',
        );
        foreach ($allowedRootProperties as $property) {
            if (isset($data[$property])) {
                $setter = 'set' . GeneralUtility::underscoredToUpperCamelCase($property);
                $event->{$setter}($data[$property]);
            }
        }
    }

    /**
     * Add date properties
     *
     * @param Event $event
     * @param array $data
     *
     * @return void
     */
    protected function addDateProperties(Event $event, array $data)
    {
        $allowedDateProperties = array(
            'event_begin',
            'event_end',
            'recurring_end',
        );
        foreach ($allowedDateProperties as $property) {
            if (!isset($data[$property])) {
                continue;
            }
            $date = \DateTime::createFromFormat('Y-m-d', $data[$property]);
            if (!$date instanceof \DateTime) {
                continue;
            }
            $setter = 'set' . GeneralUtility::underscoredToUpperCamelCase($property);
            $event->{$setter}($this->dateTimeUtility->standardizeDateTimeObject($date));
        }
    }

    /**
     * Add time properties
     *
     * @param Event $event
     * @param array $data
     *
     * @return void
     */
    protected function addTimeProperties(Event $event, array $data)
    {
        // add event time
        if (isset($data['event_time'])) {
            $eventTime = $this->objectManager->get('JWeiland\\Events2\\Domain\\Model\\Time');
            $eventTime->setTimeBegin($data['event_time']['time_begin']);
            $eventTime->setTimeEntry($data['event_time']['time_entry']);
            $eventTime->setTimeEnd($data['event_time']['time_end']);
            $eventTime->setDuration($data['event_time']['duration']);
            $event->setEventTime($eventTime);
        }

        // add multiple times for same day
        if (
            isset($data['same_day']) &&
            $data['same_day'] &&
            isset($data['multiple_times']) &&
            is_array($data['multiple_times'])
        ) {
            foreach ($data['multiple_times'] as $multipleTime) {
                $newTime = $this->objectManager->get('JWeiland\\Events2\\Domain\\Model\\Time');
                $newTime->setTimeBegin($multipleTime['time_begin']);
                $newTime->setTimeEntry($multipleTime['time_entry']);
                $newTime->setTimeEnd($multipleTime['time_end']);
                $newTime->setDuration($multipleTime['duration']);
                $event->addMultipleTime($newTime);
            }
        }

        // add different times
        if (
            isset($data['different_times']) &&
            is_array($data['different_times'])
        ) {
            foreach ($data['different_times'] as $differentTime) {
                $newTime = $this->objectManager->get('JWeiland\\Events2\\Domain\\Model\\Time');
                $newTime->setWeekday($differentTime['weekday']);
                $newTime->setTimeBegin($differentTime['time_begin']);
                $newTime->setTimeEntry($differentTime['time_entry']);
                $newTime->setTimeEnd($differentTime['time_end']);
                $newTime->setDuration($differentTime['duration']);
                $event->addDifferentTime($newTime);
            }
        }
    }

    /**
     * Add organizer
     *
     * @param Event $event
     * @param array $data
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function addOrganizer(Event $event, array $data)
    {
        $where = sprintf(
            'organizer=%s',
            $this->getDatabaseConnection()->fullQuoteStr(
                $data['organizer'],
                'tx_events2_domain_model_organizer'
            )
        );
        // I don't have the TypoScript or Plugin storage PID. That's why I don't use the repository directly
        $dbOrganizer = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            'uid',
            'tx_events2_domain_model_organizer',
            $where
        );
        if (empty($dbOrganizer)) {
            throw new \Exception('No equivalent organizer found');
        }
        $organizer = $this->organizerRepository->findByIdentifier($dbOrganizer['uid']);
        $event->setOrganizer($organizer);
    }

    /**
     * Add location
     *
     * @param Event $event
     * @param array $data
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function addLocation(Event $event, array $data)
    {
        $where = sprintf(
            'location=%s',
            $this->getDatabaseConnection()->fullQuoteStr(
                $data['location'],
                'tx_events2_domain_model_location'
            )
        );

        // I don't have the TypoScript or Plugin storage PID. That's why I don't use the repository directly
        $dbLocation = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            'uid',
            'tx_events2_domain_model_location',
            $where
        );
        if (empty($dbLocation)) {
            throw new \Exception('No equivalent location found');
        }
        $location = $this->locationRepository->findByIdentifier($dbLocation['uid']);
        $event->setLocation($location);
    }

    /**
     * Add links
     *
     * @param Event $event
     * @param array $data
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function addLinks(Event $event, array $data)
    {
        $properties = array('ticket_link', 'video_link', 'download_links');
        foreach ($properties as $property) {
            if (isset($data[$property]) && filter_var($data[$property]['uri'], FILTER_VALIDATE_URL)) {
                /** @var Link $link */
                $link = $this->objectManager->get('JWeiland\\Events2\\Domain\\Model\\Link');
                $link->setTitle($data[$property]['title']);
                $link->setLink($data[$property]['uri']);
                $methodName = 'set' . GeneralUtility::underscoredToUpperCamelCase($property);
                $event->{$methodName}($link);
            }
        }
    }

    /**
     * Add exceptions
     *
     * @param Event $event
     * @param array $data
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function addExceptions(Event $event, array $data)
    {
        if (!isset($data['exceptions']) || !is_array($data['exceptions'])) {
            return;
        }

        foreach ($data['exceptions'] as $exception) {
            /** @var Exception $newException */
            $newException = $this->objectManager->get('JWeiland\\Events2\\Domain\\Model\\Exception');
            $newException->setExceptionType($exception['exception_type']);

            $exceptionDate = \DateTime::createFromFormat('Y-m-d', $exception['exception_date']);
            if (!$exceptionDate instanceof \DateTime) {
                continue;
            }
            $newException->setExceptionDate($this->dateTimeUtility->standardizeDateTimeObject($exceptionDate));

            if (isset($exception['exception_time'])) {
                /** @var Time $newTime */
                $newTime = $this->objectManager->get('JWeiland\\Events2\\Domain\\Model\\Time');
                $newTime->setTimeBegin($exception['exception_time']['time_begin']);
                $newTime->setTimeEntry($exception['exception_time']['time_entry']);
                $newTime->setTimeEnd($exception['exception_time']['time_end']);
                $newTime->setDuration($exception['exception_time']['duration']);
                $newException->setExceptionTime($newTime);
            }

            $newException->setExceptionDetails($exception['exception_details']);

            $event->addException($newException);
        }
    }

    /**
     * Add categories
     *
     * @param Event $event
     * @param array $data
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function addCategories(Event $event, array $data)
    {
        foreach ($data['categories'] as $title) {
            $where = sprintf(
                'title=%s',
                $this->getDatabaseConnection()->fullQuoteStr($title, 'sys_category')
            );

            // I don't have the TypoScript or Plugin storage PID. That's why I don't use the repository directly
            $dbCategory = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
                'uid',
                'sys_category',
                $where
            );
            if (empty($dbCategory)) {
                throw new \Exception('No equivalent category found');
            }
            /** @var Category $category */
            $category = $this->categoryRepository->findByIdentifier($dbCategory['uid']);
            $event->addCategory($category);
        }
    }

    /**
     * Save event
     *
     * @param Event $event
     * @param AbstractTask $task
     *
     * @return void
     */
    protected function saveEvent(Event $event, AbstractTask $task)
    {
        /** @var Import $task */
        $event->setHidden(true);
        $event->setPid($task->storagePid);
        $this->getPersistenceManager()->add($event);
    }
}
