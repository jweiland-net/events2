<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Importer;

use JWeiland\Events2\Domain\Model\Category;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Exception;
use JWeiland\Events2\Domain\Model\Link;
use JWeiland\Events2\Domain\Model\Location;
use JWeiland\Events2\Domain\Model\Organizer;
use JWeiland\Events2\Domain\Model\Time;
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/*
 * Imports event records by a XML file
 */
class XmlImporter extends AbstractImporter
{
    protected array $allowedMimeType = [
        'text/xml',
        'application/xml'
    ];

    /**
     * Import XML file
     *
     * @throws \Exception
     */
    public function import(): bool
    {
        if (!$this->validateXml($this->file)) {
            return false;
        }

        $events = GeneralUtility::xml2array($this->file->getContents());
        if (is_string($events)) {
            return false;
        }

        if ($this->hasInvalidEvents($events)) {
            return false;
        }

        try {
            array_map(function (array $eventRecord): void {
                $this->processEvent($eventRecord);
            }, $events);
        } catch (\Exception $e) {
            $this->addMessage(
                $e->getMessage(),
                AbstractMessage::ERROR
            );
            return false;
        }
        $this->persistenceManager->persistAll();
        $this->addMessage('We have processed ' . count($events) . ' events');

        return true;
    }

    /**
     * Validate XML for import
     *
     * @throws \Exception
     */
    protected function validateXml(FileInterface $file): bool
    {
        try {
            libxml_use_internal_errors(true);
            $domDoc = new \DOMDocument();
            $domDoc->loadXML($file->getContents());
            if (
                !$domDoc->schemaValidate(
                    GeneralUtility::getFileAbsFileName($this->extConf->getXmlImportValidatorPath())
                )
            ) {
                foreach (libxml_get_errors() as $error) {
                    $this->addMessage(
                        sprintf(
                            'Error: %s - Line: %d',
                            $error->message,
                            $error->line
                        ),
                        AbstractMessage::ERROR
                    );
                }

                return false;
            }
        } catch (\Exception $e) {
            $this->addMessage(
                'XML does not comply with XmlImportValidator.xml.',
                AbstractMessage::ERROR
            );
            $this->addMessage(
                $e->getMessage(),
                AbstractMessage::ERROR
            );

            return false;
        }

        return true;
    }

    /**
     * Check, if an event has to be created/updated/deleted
     *
     * @throws \Exception
     */
    protected function processEvent(array $eventRecord): void
    {
        $event = $this->eventRepository->findHiddenObject((int)$eventRecord['import_id'], 'importId');
        switch ($this->getProcessAs($eventRecord)) {
            case 'delete':
                if ($event instanceof Event) {
                    $this->persistenceManager->remove($event);
                } else {
                    throw new \Exception(sprintf(
                        'Can not delete event with import-ID %s, as it does not exist in our database.',
                        $eventRecord['import_id']
                    ));
                }

                break;
            case 'edit':
                if ($event instanceof Event) {
                    // reset all properties and set them again
                    $this->addRootProperties($event, $eventRecord);

                    $event->setEventEnd(null);
                    $event->setRecurringEnd(null);
                    $this->addDateProperties($event, $eventRecord);

                    $event->setEventTime(null);
                    $event->setMultipleTimes(new ObjectStorage());
                    $event->setDifferentTimes(new ObjectStorage());
                    $this->addTimeProperties($event, $eventRecord);

                    $event->setOrganizers(new ObjectStorage());
                    $this->addOrganizers($event, $eventRecord);

                    $event->setLocation(null);
                    $this->addLocation($event, $eventRecord);

                    $event->setTicketLink(null);
                    $event->setvideoLink(null);
                    $event->setDownloadLinks(new ObjectStorage());
                    $this->addLinks($event, $eventRecord);

                    $event->setExceptions(new ObjectStorage());
                    $this->addExceptions($event, $eventRecord);

                    $event->setCategories(new ObjectStorage());
                    $this->addCategories($event, $eventRecord);

                    $event->setImages(new ObjectStorage());
                    $this->addImages($event, $eventRecord);

                    $event->setDays(new ObjectStorage());

                    $this->persistenceManager->update($event);
                } else {
                    throw new \Exception(sprintf(
                        'Can not edit event with import-ID %s, as it does not exist in our database.',
                        $eventRecord['import_id']
                    ));
                }

                break;
            case 'new':
            default:
                $event = $this->createEvent($eventRecord);
                $event->setImportId($eventRecord['import_id'] ?: '');
                $event->setHidden(true);
                $event->setPid($this->storagePid);
                $this->addPathSegment($event);
                $this->persistenceManager->add($event);

                break;
        }
    }

    protected function getProcessAs(array $eventRecord): string
    {
        $processAs = $eventRecord['process_as'] ?: 'new';
        $processAs = strtolower($processAs);
        if (!in_array($processAs, ['new', 'edit', 'delete'], true)) {
            $processAs = 'new';
        }

        return $processAs;
    }

    protected function createEvent(array $eventRecord): Event
    {
        $event = GeneralUtility::makeInstance(Event::class);
        $this->addRootProperties($event, $eventRecord);
        $this->addDateProperties($event, $eventRecord);
        $this->addTimeProperties($event, $eventRecord);
        $this->addOrganizers($event, $eventRecord);
        $this->addLocation($event, $eventRecord);
        $this->addLinks($event, $eventRecord);
        $this->addExceptions($event, $eventRecord);
        $this->addCategories($event, $eventRecord);
        $this->addImages($event, $eventRecord);

        return $event;
    }

    protected function addRootProperties(Event $event, array $eventRecord): void
    {
        $allowedRootProperties = [
            'event_type' => 'string',
            'top_of_list' => 'string',
            'title' => 'string',
            'teaser' => 'string',
            'same_day' => 'bool',
            'xth' => 'int',
            'weekday' => 'int',
            'each_weeks' => 'int',
            'detail_information' => 'string',
            'free_entry' => 'bool',
        ];

        foreach ($allowedRootProperties as $property => $dataType) {
            if (isset($eventRecord[$property])) {
                switch ($dataType) {
                    case 'int':
                        $this->setEventProperty($event, $property, (int)$eventRecord[$property]);
                        break;
                    case 'bool':
                        $this->setEventProperty($event, $property, (bool)$eventRecord[$property]);
                        break;
                    case 'string':
                    default:
                        $this->setEventProperty($event, $property, (string)$eventRecord[$property]);
                        break;
                }
            }
        }
    }

    /**
     * This method will store the event, if no UID was given (needed for URL like title-uid.html). So please keep
     * this method at last position just before repo will add event, but just after event was set to hidden.
     */
    protected function addPathSegment(Event $event): void
    {
        $this->pathSegmentHelper->updatePathSegmentForEvent($event);
    }

    protected function addDateProperties(Event $event, array $eventRecord): void
    {
        $allowedDateProperties = [
            'event_begin',
            'event_end',
            'recurring_end',
        ];
        foreach ($allowedDateProperties as $property) {
            if (!isset($eventRecord[$property])) {
                continue;
            }
            $date = \DateTimeImmutable::createFromFormat('Y-m-d', $eventRecord[$property]);
            if (!$date instanceof \DateTimeImmutable) {
                continue;
            }

            $this->setEventProperty($event, $property, $this->dateTimeUtility->standardizeDateTimeObject($date));
        }
    }

    protected function addTimeProperties(Event $event, array $eventRecord): void
    {
        // add event time
        if (isset($eventRecord['event_time']) && is_array($eventRecord['event_time'])) {
            $eventTime = GeneralUtility::makeInstance(Time::class);
            $eventTime->setPid($this->storagePid);
            $eventTime->setTimeBegin($eventRecord['event_time']['time_begin'] ?: '');
            $eventTime->setTimeEntry($eventRecord['event_time']['time_entry'] ?: '');
            $eventTime->setTimeEnd($eventRecord['event_time']['time_end'] ?: '');
            $eventTime->setDuration($eventRecord['event_time']['duration'] ?: '');
            $event->setEventTime($eventTime);
        }

        // add multiple times for same day
        if (
            isset($eventRecord['same_day'], $eventRecord['multiple_times'])
            && $eventRecord['same_day']
            && is_array($eventRecord['multiple_times'])
        ) {
            foreach ($eventRecord['multiple_times'] as $multipleTime) {
                $newTime = GeneralUtility::makeInstance(Time::class);
                $newTime->setPid($this->storagePid);
                $newTime->setTimeBegin($multipleTime['time_begin'] ?: '');
                $newTime->setTimeEntry($multipleTime['time_entry'] ?: '');
                $newTime->setTimeEnd($multipleTime['time_end'] ?: '');
                $newTime->setDuration($multipleTime['duration'] ?: '');
                $event->addMultipleTime($newTime);
            }
        }

        // add different times
        if (!isset($eventRecord['different_times'])) {
            return;
        }

        if (!is_array($eventRecord['different_times'])) {
            return;
        }

        foreach ($eventRecord['different_times'] as $differentTime) {
            $newTime = GeneralUtility::makeInstance(Time::class);
            $newTime->setPid($this->storagePid);
            $newTime->setWeekday($differentTime['weekday']);
            $newTime->setTimeBegin($differentTime['time_begin'] ?: '');
            $newTime->setTimeEntry($differentTime['time_entry'] ?: '');
            $newTime->setTimeEnd($differentTime['time_end'] ?: '');
            $newTime->setDuration($differentTime['duration'] ?: '');
            $event->addDifferentTime($newTime);
        }
    }

    protected function addOrganizers(Event $event, array $eventRecord): void
    {
        if ($this->areOrganizersProcessable($eventRecord)) {
            foreach ($eventRecord['organizers'] as $organizerName) {
                $organizerFromDatabase = $this->getOrganizer($organizerName);

                /** @var Organizer $organizerObject */
                $organizerObject = $this->organizerRepository->findByIdentifier($organizerFromDatabase['uid']);
                $event->addOrganizer($organizerObject);
            }
        }
    }

    protected function addLocation(Event $event, array $eventRecord): void
    {
        if ($this->isLocationProcessable($eventRecord)) {
            $locationFromDatabase = $this->getLocation($eventRecord['location']);

            /** @var Location $locationObject */
            $locationObject = $this->locationRepository->findByIdentifier($locationFromDatabase['uid']);
            $event->setLocation($locationObject);
        }
    }

    protected function addLinks(Event $event, array $eventRecord): void
    {
        $properties = ['ticket_link', 'video_link', 'download_links'];
        foreach ($properties as $property) {
            if (isset($eventRecord[$property]) && filter_var($eventRecord[$property]['uri'], FILTER_VALIDATE_URL)) {
                /** @var Link $link */
                $link = GeneralUtility::makeInstance(Link::class);
                $link->setPid($this->storagePid);
                $link->setTitle($eventRecord[$property]['title']);
                $link->setLink($eventRecord[$property]['uri']);

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

    protected function addExceptions(Event $event, array $eventRecord): void
    {
        if (!isset($eventRecord['exceptions'])) {
            return;
        }

        if (!is_array($eventRecord['exceptions'])) {
            return;
        }

        foreach ($eventRecord['exceptions'] as $exception) {
            $newException = GeneralUtility::makeInstance(Exception::class);
            $newException->setPid($this->storagePid);
            $newException->setExceptionType($exception['exception_type']);

            $exceptionDate = \DateTimeImmutable::createFromFormat('Y-m-d', $exception['exception_date']);
            if (!$exceptionDate instanceof \DateTimeImmutable) {
                continue;
            }

            $newException->setExceptionDate($this->dateTimeUtility->standardizeDateTimeObject($exceptionDate));

            if (isset($exception['exception_time'])) {
                $newTime = GeneralUtility::makeInstance(Time::class);
                $newTime->setPid($this->storagePid);
                $newTime->setTimeBegin($exception['exception_time']['time_begin'] ?: '');
                $newTime->setTimeEntry($exception['exception_time']['time_entry'] ?: '');
                $newTime->setTimeEnd($exception['exception_time']['time_end'] ?: '');
                $newTime->setDuration($exception['exception_time']['duration'] ?: '');
                $newException->setExceptionTime($newTime);
            }

            $newException->setExceptionDetails($exception['exception_details']);

            $event->addException($newException);
        }
    }

    protected function addCategories(Event $event, array $eventRecord): void
    {
        foreach ($eventRecord['categories'] as $title) {
            $dbCategory = $this->getCategory($title);
            /** @var Category $category */
            $category = $this->categoryRepository->findByIdentifier($dbCategory['uid']);
            $event->addCategory($category);
        }
    }

    protected function addImages(Event $event, array $eventRecord): void
    {
        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        if (isset($eventRecord['images']) && is_array($eventRecord['images'])) {
            $images = new ObjectStorage();
            /** @var CharsetConverter $csConverter */
            $csConverter = GeneralUtility::makeInstance(CharsetConverter::class);
            foreach ($eventRecord['images'] as $image) {
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
                $targetDirectoryPath = Environment::getPublicPath() . '/' . $rootFolder->getPublicUrl() . $relativeTargetDirectoryPath;
                GeneralUtility::mkdir_deep($targetDirectoryPath);

                $targetFolder = $resourceFactory->getFolderObjectFromCombinedIdentifier(
                    $rootFolder->getCombinedIdentifier() . $relativeTargetDirectoryPath
                );
                if ($targetFolder->hasFile($filename)) {
                    $file = $resourceFactory->retrieveFileOrFolderObject(
                        $targetFolder->getCombinedIdentifier() . $filename
                    );
                } else {
                    $report = [];
                    $content = GeneralUtility::getUrl($image['url']);
                    if (!empty($report['error'])) {
                        $this->addMessage(sprintf(
                            'Given image was NOT added to event. Error: %s',
                            $report['message']
                        ), AbstractMessage::NOTICE);
                        continue;
                    }

                    $file = $targetFolder->createFile($filename);
                    $file->setContents($content);
                }

                // Create new FileReference
                $extbaseFileReference = GeneralUtility::makeInstance(FileReference::class);
                $extbaseFileReference->setPid($this->storagePid);
                $extbaseFileReference->setOriginalResource($resourceFactory->createFileReferenceObject(
                    [
                        'uid_local' => $file->getUid(),
                        'uid_foreign' => uniqid('NEW_', true),
                        'uid' => uniqid('NEW_', true)
                    ]
                ));

                $images->attach($extbaseFileReference);
            }

            $event->setImages($images);
        }
    }
}
