<?php

namespace JWeiland\Events2\Importer;

/*
 * This file is part of the events2 project.
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
use JWeiland\Events2\Domain\Model\Time;
use JWeiland\Events2\Task\Import;
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Imports event records by a XML file
 */
class XmlImporter extends AbstractImporter
{
    /**
     * @var int
     */
    protected $storagePid = 0;

    /**
     * @var array
     */
    protected $allowedMimeType = [
        'text/xml',
        'application/xml'
    ];

    /**
     * Import XML file
     *
     * @param FileInterface $file
     * @param AbstractTask $task
     * @return bool
     * @throws \Exception
     */
    public function import(FileInterface $file, AbstractTask $task)
    {
        if (!$this->validateXml($file)) {
            return false;
        }

        $this->storagePid = (int)$task->storagePid;

        $events = GeneralUtility::xml2array($file->getContents());
        if ($this->hasInvalidEvents($events)) {
            return false;
        }

        foreach ($events as $event) {
            $this->saveEvent(
                $this->createEvent($event)
            );
        }
        $this->getPersistenceManager()->persistAll();
        $this->addMessage('We have imported ' . count($events) . ' events');

        return true;
    }

    /**
     * Validate XML for import
     *
     * @param FileInterface $file
     * @return bool
     * @throws \Exception
     */
    protected function validateXml(FileInterface $file)
    {
        try {
            libxml_use_internal_errors(true);
            $domDoc = new \DOMDocument();
            $domDoc->loadXML($file->getContents());
            if (!$domDoc->schemaValidate(PATH_site . 'typo3conf/ext/events2/Resources/Public/XmlImportValidator.xsd')) {
                foreach (libxml_get_errors() as $error) {
                    $this->addMessage(
                        sprintf(
                            'Error: %s - Line: %d',
                            $error->message,
                            $error->line
                        ),
                        FlashMessage::ERROR
                    );
                }
                return false;
            }
        } catch (\Exception $e) {
            $this->addMessage(
                'XML does not comply with XmlImportValidator.xml.',
                FlashMessage::ERROR
            );
            $this->addMessage(
                $e->getMessage(),
                FlashMessage::ERROR
            );
            return false;
        }
        return true;
    }

    /**
     * Create new event
     *
     * @param array $data
     * @return Event
     * @throws \Exception
     */
    protected function createEvent(array $data)
    {
        /** @var Event $event */
        $event = $this->objectManager->get(Event::class);
        $this->addRootProperties($event, $data);
        $this->addDateProperties($event, $data);
        $this->addTimeProperties($event, $data);
        $this->addOrganizer($event, $data);
        $this->addLocation($event, $data);
        $this->addLinks($event, $data);
        $this->addExceptions($event, $data);
        $this->addCategories($event, $data);
        $this->addImages($event, $data);

        return $event;
    }

    /**
     * Add root properties
     *
     * @param Event $event
     * @param array $data
     * @return void
     */
    protected function addRootProperties(Event $event, array $data)
    {
        $allowedRootProperties = [
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
        ];
        foreach ($allowedRootProperties as $property) {
            if (isset($data[$property])) {
                $this->setEventProperty($event, $property, $data[$property]);
            }
        }
    }

    /**
     * Add date properties
     *
     * @param Event $event
     * @param array $data
     * @return void
     */
    protected function addDateProperties(Event $event, array $data)
    {
        $allowedDateProperties = [
            'event_begin',
            'event_end',
            'recurring_end',
        ];
        foreach ($allowedDateProperties as $property) {
            if (!isset($data[$property])) {
                continue;
            }
            $date = \DateTime::createFromFormat('Y-m-d', $data[$property]);
            if (!$date instanceof \DateTime) {
                continue;
            }
            $this->setEventProperty($event, $property, $this->dateTimeUtility->standardizeDateTimeObject($date));
        }
    }

    /**
     * Add time properties
     *
     * @param Event $event
     * @param array $data
     * @return void
     */
    protected function addTimeProperties(Event $event, array $data)
    {
        // add event time
        if (isset($data['event_time']) && is_array($data['event_time'])) {
            /** @var Time $eventTime */
            $eventTime = $this->objectManager->get(Time::class);
            $eventTime->setPid($this->storagePid);
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
                $newTime = $this->objectManager->get(Time::class);
                $newTime->setPid($this->storagePid);
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
                $newTime = $this->objectManager->get(Time::class);
                $newTime->setPid($this->storagePid);
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
     * @return void
     */
    protected function addOrganizer(Event $event, array $data)
    {
        $organizer = $this->getOrganizer($data['organizer']);
        $event->setOrganizer($this->organizerRepository->findByIdentifier($organizer['uid']));
    }

    /**
     * Add location
     *
     * @param Event $event
     * @param array $data
     * @return void
     * @throws \Exception
     */
    protected function addLocation(Event $event, array $data)
    {
        $location = $this->getLocation($data['location']);
        $event->setLocation($this->locationRepository->findByIdentifier($location['uid']));
    }

    /**
     * Add links
     *
     * @param Event $event
     * @param array $data
     * @return void
     * @throws \Exception
     */
    protected function addLinks(Event $event, array $data)
    {
        $properties = ['ticket_link', 'video_link', 'download_links'];
        foreach ($properties as $property) {
            if (isset($data[$property]) && filter_var($data[$property]['uri'], FILTER_VALIDATE_URL)) {
                /** @var Link $link */
                $link = $this->objectManager->get(Link::class);
                $link->setPid($this->storagePid);
                $link->setTitle($data[$property]['title']);
                $link->setLink($data[$property]['uri']);

                if ($property === 'download_links') {
                    $objectStorage = new ObjectStorage();
                    $objectStorage->attach($link);
                    $this->setEventProperty($event, $property, $objectStorage);
                } else {
                    $this->setEventProperty($event, $property, $link);
                }
            }
        }
    }

    /**
     * Add exceptions
     *
     * @param Event $event
     * @param array $data
     * @return void
     * @throws \Exception
     */
    protected function addExceptions(Event $event, array $data)
    {
        if (!isset($data['exceptions']) || !is_array($data['exceptions'])) {
            return;
        }

        foreach ($data['exceptions'] as $exception) {
            /** @var Exception $newException */
            $newException = $this->objectManager->get(Exception::class);
            $newException->setPid($this->storagePid);
            $newException->setExceptionType($exception['exception_type']);

            $exceptionDate = \DateTime::createFromFormat('Y-m-d', $exception['exception_date']);
            if (!$exceptionDate instanceof \DateTime) {
                continue;
            }
            $newException->setExceptionDate($this->dateTimeUtility->standardizeDateTimeObject($exceptionDate));

            if (isset($exception['exception_time'])) {
                /** @var Time $newTime */
                $newTime = $this->objectManager->get(Time::class);
                $newTime->setPid($this->storagePid);
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
     * @return void
     * @throws \Exception
     */
    protected function addCategories(Event $event, array $data)
    {
        foreach ($data['categories'] as $title) {
            $dbCategory = $this->getCategory($title);
            /** @var Category $category */
            $category = $this->categoryRepository->findByIdentifier($dbCategory['uid']);
            $event->addCategory($category);
        }
    }

    /**
     * Add images
     *
     * @param Event $event
     * @param array $data
     * @return void
     * @throws \Exception
     */
    protected function addImages(Event $event, array $data)
    {
        if (isset($data['images']) && is_array($data['images'])) {
            $images = new ObjectStorage();
            /** @var \TYPO3\CMS\Core\Charset\CharsetConverter $csConverter */
            $csConverter = GeneralUtility::makeInstance(CharsetConverter::class);
            foreach ($data['images'] as $image) {
                // we try to keep the original structure from origin server to prevent duplicate filenames
                $filePath = parse_url($image['url'], PHP_URL_PATH);
                $fileParts = GeneralUtility::split_fileref($filePath);
                $filename = $csConverter->specCharsToASCII(
                    'utf-8',
                    rawurldecode($fileParts['file'])
                );

                /** @var Folder $rootFolder */
                $rootFolder = $this->file->getParentFolder();
                $relativeTargetDirectoryPath = sprintf(
                    'Images/%d/%d/%d/%s',
                    $this->today->format('Y'),
                    $this->today->format('m'),
                    $this->today->format('d'),
                    $this->today->format('His')

                );
                $targetDirectoryPath = PATH_site . $rootFolder->getPublicUrl() . $relativeTargetDirectoryPath;
                GeneralUtility::mkdir_deep($targetDirectoryPath);

                /** @var Folder $targetFolder */
                $targetFolder = ResourceFactory::getInstance()->getFolderObjectFromCombinedIdentifier(
                    $rootFolder->getCombinedIdentifier() . $relativeTargetDirectoryPath
                );
                if ($targetFolder->hasFile($filename)) {
                    $file = ResourceFactory::getInstance()->retrieveFileOrFolderObject(
                        $targetFolder->getCombinedIdentifier() . $filename
                    );
                } else {
                    $report = [];
                    $content = GeneralUtility::getUrl($image['url'], 0, null, $report);
                    if (!empty($report['error'])) {
                        $this->addMessage(sprintf(
                            'Given image was NOT added to event. Error: %s',
                            $report['message']
                        ), FlashMessage::NOTICE);
                        continue;
                    } else {
                        $file = $targetFolder->createFile($filename);
                        $file->setContents($content);
                    }
                }

                // Create new FileReference
                /** @var \TYPO3\CMS\Extbase\Domain\Model\FileReference $extbaseFileReference */
                $extbaseFileReference = $this->objectManager->get(\TYPO3\CMS\Extbase\Domain\Model\FileReference::class);
                $extbaseFileReference->setPid($this->storagePid);
                $extbaseFileReference->setOriginalResource(ResourceFactory::getInstance()->createFileReferenceObject(
                    [
                        'uid_local' => $file->getUid(),
                        'uid_foreign' => uniqid('NEW_'),
                        'uid' => uniqid('NEW_')
                    ]
                ));
                $images->attach($extbaseFileReference);
            }
            $event->setImages($images);
        }
    }

    /**
     * Save event
     *
     * @param Event $event
     * @return void
     */
    protected function saveEvent(Event $event)
    {
        /** @var Import $task */
        $event->setHidden(true);
        $event->setPid($this->storagePid);
        $this->getPersistenceManager()->add($event);
    }
}
