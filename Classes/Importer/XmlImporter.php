<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Importer;

use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Domain\Model\Category;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Exception;
use JWeiland\Events2\Domain\Model\Link;
use JWeiland\Events2\Domain\Model\Location;
use JWeiland\Events2\Domain\Model\Organizer;
use JWeiland\Events2\Domain\Model\Time;
use JWeiland\Events2\Domain\Repository\CategoryRepository;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Domain\Repository\LocationRepository;
use JWeiland\Events2\Domain\Repository\OrganizerRepository;
use JWeiland\Events2\Helper\PathSegmentHelper;
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

/**
 * This is an importer for a very simple events2 record structure.
 * The properties may not be the same as in database tables.
 *
 * If you need to import something more database structure related please use JsonImporter (req: EXT:reactions).
 */
class XmlImporter
{
    protected int $storagePid = 0;

    /**
     * The file to import
     */
    protected FileInterface $file;

    protected string $logFileName = 'Messages.txt';

    protected \DateTimeImmutable $today;

    protected array $allowedMimeType = [
        'text/xml',
        'application/xml',
    ];

    public function __construct(
        protected readonly EventRepository $eventRepository,
        protected readonly OrganizerRepository $organizerRepository,
        protected readonly LocationRepository $locationRepository,
        protected readonly CategoryRepository $categoryRepository,
        protected readonly PersistenceManagerInterface $persistenceManager,
        protected readonly PathSegmentHelper $pathSegmentHelper,
        protected readonly DateTimeUtility $dateTimeUtility,
        protected readonly ExtConf $extConf,
    ) {
        $this->today = new \DateTimeImmutable('now');
    }

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
                ContextualFeedbackSeverity::ERROR,
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
                    GeneralUtility::getFileAbsFileName($this->extConf->getXmlImportValidatorPath()),
                )
            ) {
                foreach (libxml_get_errors() as $error) {
                    $this->addMessage(
                        sprintf(
                            'Error: %s - Line: %d',
                            $error->message,
                            $error->line,
                        ),
                        ContextualFeedbackSeverity::ERROR,
                    );
                }

                return false;
            }
        } catch (\Exception $e) {
            $this->addMessage(
                'XML does not comply with XmlImportValidator.xml.',
                ContextualFeedbackSeverity::ERROR,
            );
            $this->addMessage(
                $e->getMessage(),
                ContextualFeedbackSeverity::ERROR,
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
                        $eventRecord['import_id'],
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
                        $eventRecord['import_id'],
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
            $eventTime->setTimeBegin($eventRecord['event_time']['time_begin'] ?? '');
            $eventTime->setTimeEntry($eventRecord['event_time']['time_entry'] ?? '');
            $eventTime->setTimeEnd($eventRecord['event_time']['time_end'] ?? '');
            $eventTime->setDuration($eventRecord['event_time']['duration'] ?? '');
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
                $newTime->setTimeBegin($multipleTime['time_begin'] ?? '');
                $newTime->setTimeEntry($multipleTime['time_entry'] ?? '');
                $newTime->setTimeEnd($multipleTime['time_end'] ?? '');
                $newTime->setDuration($multipleTime['duration'] ?? '');
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
            $newTime->setTimeBegin($differentTime['time_begin'] ?? '');
            $newTime->setTimeEntry($differentTime['time_entry'] ?? '');
            $newTime->setTimeEnd($differentTime['time_end'] ?? '');
            $newTime->setDuration($differentTime['duration'] ?? '');
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
                $newTime->setTimeBegin($exception['exception_time']['time_begin'] ?? '');
                $newTime->setTimeEntry($exception['exception_time']['time_entry'] ?? '');
                $newTime->setTimeEnd($exception['exception_time']['time_end'] ?? '');
                $newTime->setDuration($exception['exception_time']['duration'] ?? '');
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
                    rawurldecode($fileParts['file']),
                );

                /** @var Folder $rootFolder */
                $rootFolder = $this->file->getParentFolder();
                $relativeTargetDirectoryPath = sprintf(
                    'Images/%d/%d/%d/%s',
                    $this->today->format('Y'),
                    $this->today->format('m'),
                    $this->today->format('d'),
                    $this->today->format('His'),
                );
                $targetDirectoryPath = Environment::getPublicPath() . '/' . $rootFolder->getPublicUrl() . $relativeTargetDirectoryPath;
                GeneralUtility::mkdir_deep($targetDirectoryPath);

                $targetFolder = $resourceFactory->getFolderObjectFromCombinedIdentifier(
                    $rootFolder->getCombinedIdentifier() . $relativeTargetDirectoryPath,
                );
                if ($targetFolder->hasFile($filename)) {
                    $file = $resourceFactory->retrieveFileOrFolderObject(
                        $targetFolder->getCombinedIdentifier() . $filename,
                    );
                } else {
                    $file = $targetFolder->createFile($filename);
                    $file->setContents(GeneralUtility::getUrl($image['url']));
                }

                // Create new FileReference
                $extbaseFileReference = GeneralUtility::makeInstance(FileReference::class);
                $extbaseFileReference->setPid($this->storagePid);
                $extbaseFileReference->setOriginalResource($resourceFactory->createFileReferenceObject(
                    [
                        'uid_local' => $file->getUid(),
                        'uid_foreign' => uniqid('NEW_', true),
                        'uid' => uniqid('NEW_', true),
                    ],
                ));

                $images->attach($extbaseFileReference);
            }

            $event->setImages($images);
        }
    }

    public function setStoragePid(int $storagePid): void
    {
        $this->storagePid = $storagePid;
    }

    public function setFile(FileInterface $file): void
    {
        $this->file = $file;
    }

    public function checkFile(): bool
    {
        $isValid = true;

        if (!in_array($this->file->getMimeType(), $this->allowedMimeType, true)) {
            $isValid = false;
            $this->addMessage('MimeType of file is not allowed', ContextualFeedbackSeverity::ERROR);
        }

        return $isValid;
    }

    protected function hasInvalidEvents(array $events): bool
    {
        foreach ($events as $event) {
            if (!$this->isValidEvent($event)) {
                return true;
            }
        }

        return false;
    }

    protected function isValidEvent(array $event): bool
    {
        // Is future event?
        $eventBegin = \DateTimeImmutable::createFromFormat('Y-m-d', $event['event_begin']);
        if ($eventBegin < $this->today) {
            $this->addMessage(
                sprintf(
                    'Event: %s - Date: %s - Error: %s',
                    $event['title'],
                    $eventBegin->format('d.m.Y'),
                    'event_begin can not be in past',
                ),
                ContextualFeedbackSeverity::ERROR,
            );

            return false;
        }

        if ($this->areOrganizersProcessable($event)) {
            foreach ($event['organizers'] as $organizer) {
                if ($this->getOrganizer($organizer) === []) {
                    $this->addNotFoundMessage($event, 'organizer', $organizer, $eventBegin);

                    return false;
                }
            }
        }

        if ($this->isLocationProcessable($event) && $this->getLocation($event['location']) === []) {
            $this->addNotFoundMessage($event, 'location', $event['location'], $eventBegin);

            return false;
        }

        if (isset($event['categories']) && is_array($event['categories'])) {
            foreach ($event['categories'] as $title) {
                if ($this->getCategory($title) === []) {
                    $this->addNotFoundMessage($event, 'category', $title, $eventBegin);

                    return false;
                }
            }
        }

        // check for valid image paths
        if (!isset($event['images'])) {
            return true;
        }
        if (!is_array($event['images'])) {
            return true;
        }

        foreach ($event['images'] as $image) {
            if (!is_array($image)) {
                $this->addMessage(
                    sprintf(
                        'Event: %s - Date: %s - Error: %s',
                        $event['title'],
                        $eventBegin->format('d.m.Y'),
                        'Image must be of type array',
                    ),
                    ContextualFeedbackSeverity::ERROR,
                );

                return false;
            }
            if (!isset($image['url']) || empty(trim($image['url']))) {
                $this->addMessage(
                    sprintf(
                        'Event: %s - Date: %s - Error: %s',
                        $event['title'],
                        $eventBegin->format('d.m.Y'),
                        'Array key "url" of image must be set and can not be empty',
                    ),
                    ContextualFeedbackSeverity::ERROR,
                );

                return false;
            }
            if (!filter_var($image['url'], FILTER_VALIDATE_URL)) {
                $this->addMessage(
                    sprintf(
                        'Event: %s - Date: %s - Error: %s',
                        $event['title'],
                        $eventBegin->format('d.m.Y'),
                        'Image path has to be a valid URL',
                    ),
                    ContextualFeedbackSeverity::ERROR,
                );

                return false;
            }
        }

        return true;
    }

    protected function addNotFoundMessage(array $event, string $property, string $value, \DateTimeImmutable $date): void
    {
        $this->addMessage(
            sprintf(
                'Event: %s - Date: %s - Error: %s',
                $event['title'],
                $date->format('d.m.Y'),
                sprintf(
                    'Given %s "%s" does not exist in our database',
                    $property,
                    $value,
                ),
            ),
            ContextualFeedbackSeverity::ERROR,
        );
    }

    protected function areOrganizersProcessable(array $event): bool
    {
        if (!$this->extConf->getOrganizerIsRequired()) {
            return false;
        }

        if (
            array_key_exists('organizers', $event)
            && is_array($event['organizers'])
        ) {
            foreach ($event['organizers'] as $organizer) {
                if (empty($organizer)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    protected function getOrganizer(string $title): ?array
    {
        if ($title === '') {
            return [];
        }

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_organizer');
        $organizer = $queryBuilder
            ->select('uid')
            ->from('tx_events2_domain_model_organizer')
            ->where(
                $queryBuilder->expr()->eq(
                    'organizer',
                    $queryBuilder->createNamedParameter($title),
                ),
            )
            ->executeQuery()
            ->fetchAssociative();

        return $organizer ?: [];
    }

    protected function isLocationProcessable(array $event): bool
    {
        if (!$this->extConf->getLocationIsRequired()) {
            return false;
        }

        if (!array_key_exists('location', $event)) {
            return false;
        }

        return $event['location'] !== '';
    }

    protected function getLocation(string $title): array
    {
        if ($title === '') {
            return [];
        }

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_location');

        // I don't have the TypoScript or Plugin storage PID. That's why I don't use the repository directly
        $location = $queryBuilder
            ->select('uid')
            ->from('tx_events2_domain_model_location')
            ->where(
                $queryBuilder->expr()->eq(
                    'location',
                    $queryBuilder->createNamedParameter($title),
                ),
            )
            ->executeQuery()
            ->fetchAssociative();

        return $location ?: [];
    }

    protected function getCategory(string $title): array
    {
        if ($title === '') {
            return [];
        }

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_category');
        $category = $queryBuilder
            ->select('uid')
            ->from('sys_category')
            ->where(
                $queryBuilder->expr()->eq(
                    'title',
                    $queryBuilder->createNamedParameter($title),
                ),
            )
            ->executeQuery()
            ->fetchAssociative();

        return $category ?: [];
    }

    protected function addMessage(string $message, ContextualFeedbackSeverity $severity = ContextualFeedbackSeverity::OK): void
    {
        static $firstMessage = true;
        /** @var AbstractFile $logFile */
        static $logFile = null;

        try {
            $content = '';
            if ($firstMessage) {
                // truncate LogFile
                $logFile = $this->getLogFile();
                $logFile->setContents($content);
                $firstMessage = false;
            } else {
                $content = $logFile->getContents();
            }

            $logFile->setContents($content . $message . LF);
        } catch (\Exception $exception) {
            $message = $exception->getMessage();
            $severity = ContextualFeedbackSeverity::ERROR;
        }

        // show messages in TYPO3 BE when started manually
        $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, '', $severity);
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }

    /**
     * Get LogFile
     * If it does not exists, we create a new one in same directory of import file
     *
     * @throws \Exception
     */
    protected function getLogFile(): AbstractFile
    {
        try {
            /** @var Folder $folder */
            $folder = $this->file->getParentFolder();
            if (!$folder->hasFile($this->logFileName)) {
                $logFile = $folder->createFile($this->logFileName);
            } else {
                $logFile = GeneralUtility::makeInstance(ResourceFactory::class)->retrieveFileOrFolderObject(
                    $folder->getCombinedIdentifier() . $this->logFileName,
                );
            }
        } catch (\Exception $e) {
            throw new \Exception('Error while retrieving the LogFile. FAL error: ' . $e->getMessage(), 1525416333);
        }

        return $logFile;
    }

    protected function setEventProperty(Event $event, string $column, $value): void
    {
        $setter = 'set' . GeneralUtility::underscoredToUpperCamelCase($column);
        if (method_exists($event, $setter)) {
            $event->{$setter}($value);
        }
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
