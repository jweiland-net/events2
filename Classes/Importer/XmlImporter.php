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
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class XmlImporter extends AbstractImporter
{
    /**
     * @var array
     */
    protected $allowedMimeType = [
        'application/xml'
    ];

    /**
     * Import XML file
     *
     * @param FileInterface $file
     * @param AbstractTask $task
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function import(FileInterface $file, AbstractTask $task)
    {
        if (!$this->validateXml($file)) {
            $this->addMessage('XML file does not match XSD file');
            return false;
        }
        $events = GeneralUtility::xml2array($file->getContents());
        if ($this->hasInvalidEvents($events)) {
            return false;
        }

        foreach ($events as $event) {
            $this->saveEvent(
                $this->createEvent($event),
                $task
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
     *
     * @return bool
     *
     * @throws \Exception
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
     * Create new event
     *
     * @param array $data
     *
     * @return Event
     *
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
     *
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
     *
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
     *
     * @return void
     */
    protected function addTimeProperties(Event $event, array $data)
    {
        // add event time
        if (isset($data['event_time']) && is_array($data['event_time'])) {
            /** @var Time $eventTime */
            $eventTime = $this->objectManager->get(Time::class);
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
     *
     * @return void
     *
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
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function addLinks(Event $event, array $data)
    {
        $properties = ['ticket_link', 'video_link', 'download_links'];
        foreach ($properties as $property) {
            if (isset($data[$property]) && filter_var($data[$property]['uri'], FILTER_VALIDATE_URL)) {
                /** @var Link $link */
                $link = $this->objectManager->get(Link::class);
                $link->setTitle($data[$property]['title']);
                $link->setLink($data[$property]['uri']);
                $this->setEventProperty($event, $property, $link);
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
            $newException = $this->objectManager->get(Exception::class);
            $newException->setExceptionType($exception['exception_type']);

            $exceptionDate = \DateTime::createFromFormat('Y-m-d', $exception['exception_date']);
            if (!$exceptionDate instanceof \DateTime) {
                continue;
            }
            $newException->setExceptionDate($this->dateTimeUtility->standardizeDateTimeObject($exceptionDate));

            if (isset($exception['exception_time'])) {
                /** @var Time $newTime */
                $newTime = $this->objectManager->get(Time::class);
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
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function addImages(Event $event, array $data)
    {
        if (isset($data['images']) && is_array($data['images'])) {
            $images = new ObjectStorage();
            foreach ($data['images'] as $image) {
                // error handling
                if (!is_array($image)) {
                    $this->addMessage('Image must be of type array', FlashMessage::WARNING);
                    continue;
                }
                if (!isset($image['url']) || empty(trim($image['url']))) {
                    $this->addMessage('Array key "url" of image must be set and can not be empty', FlashMessage::WARNING);
                    continue;
                }
                if (!filter_var($image['url'], FILTER_VALIDATE_URL)) {
                    $this->addMessage('Image path has to be a valid URL', FlashMessage::WARNING);
                    continue;
                }

                // we try to keep the original structure from origin server to prevent duplicate filenames
                $filePath = parse_url($image['url'], PHP_URL_PATH);
                $fileParts = GeneralUtility::split_fileref($filePath);
                $filename = $fileParts['file'];

                /** @var Folder $rootFolder */
                $rootFolder = $this->file->getParentFolder();
                $relativeTargetDirectoryPath = 'Images/' . $fileParts['path'];
                $targetDirectoryPath = PATH_site . $rootFolder->getPublicUrl() . $relativeTargetDirectoryPath;
                GeneralUtility::mkdir_deep($targetDirectoryPath);

                /** @var Folder $targetFolder */
                $targetFolder = ResourceFactory::getInstance()->getFolderObjectFromCombinedIdentifier(
                    $rootFolder->getCombinedIdentifier() . $relativeTargetDirectoryPath
                );
                if ($targetFolder->hasFile($filename)) {
                    $file = ResourceFactory::getInstance()->retrieveFileOrFolderObject(
                        $rootFolder->getCombinedIdentifier() . $relativeTargetDirectoryPath . $filename
                    );
                } else {
                    $file = $targetFolder->createFile($filename);
                    $file->setContents(GeneralUtility::getUrl($image['url']));
                }

                // Create new FileReference
                /** @var \TYPO3\CMS\Extbase\Domain\Model\FileReference $extbaseFileReference */
                $extbaseFileReference = $this->objectManager->get(\TYPO3\CMS\Extbase\Domain\Model\FileReference::class);
                $extbaseFileReference->setOriginalResource(ResourceFactory::getInstance()->createFileReferenceObject(
                    [
                        'uid_local' => $file->getUid(),
                        'uid_foreign' => uniqid('NEW_'),
                        'uid' => uniqid('NEW_'),
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
