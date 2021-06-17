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
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Repository\CategoryRepository;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Domain\Repository\LocationRepository;
use JWeiland\Events2\Domain\Repository\OrganizerRepository;
use JWeiland\Events2\Task\Import;
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

/*
 * Abstract Importer which will keep most methods for all importer scripts
 */
abstract class AbstractImporter implements ImporterInterface
{
    /**
     * @var int
     */
    protected $storagePid = 0;

    /**
     * The file to import
     *
     * @var FileInterface
     */
    protected $file;

    /**
     * Needed to retrieve the storagePid
     *
     * @deprecated
     * @var Import
     */
    protected $task;

    /**
     * @var string
     */
    protected $logFileName = 'Messages.txt';

    /**
     * @var array
     */
    protected $allowedMimeType = [];

    /**
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var EventRepository
     */
    protected $eventRepository;

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
     * @var DateTimeUtility
     */
    protected $dateTimeUtility;

    /**
     * @var ExtConf
     */
    protected $extConf;

    /**
     * @var \DateTime
     */
    protected $today;

    public function __construct(
        EventRepository $eventRepository,
        OrganizerRepository $organizerRepository,
        LocationRepository $locationRepository,
        CategoryRepository $categoryRepository,
        PersistenceManagerInterface $persistenceManager,
        DateTimeUtility $dateTimeUtility,
        ExtConf $extConf
    ) {
        $this->eventRepository = $eventRepository;
        $this->organizerRepository = $organizerRepository;
        $this->locationRepository = $locationRepository;
        $this->categoryRepository = $categoryRepository;
        $this->persistenceManager = $persistenceManager;
        $this->dateTimeUtility = $dateTimeUtility;
        $this->extConf = $extConf;
        $this->today = new \DateTime('now');
    }

    /**
     * @param Import $task
     * @deprecated will be removed in events2 7.0.0. Please set the storagePid directly
     */
    public function setTask(Import $task): void
    {
        trigger_error('setTask() will be removed in events2 7.0.0. Please set the storagePid directly.', E_USER_DEPRECATED);
        $this->task = $task;
    }

    /**
     * @param int $storagePid
     */
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

        if (!in_array($this->file->getMimeType(), $this->allowedMimeType)) {
            $isValid = false;
            $this->addMessage('MimeType of file is not allowed', FlashMessage::ERROR);
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
        $eventBegin = \DateTime::createFromFormat('Y-m-d', $event['event_begin']);
        if ($eventBegin < $this->today) {
            $this->addMessage(
                sprintf(
                    'Event: %s - Date: %s - Error: %s',
                    $event['title'],
                    $eventBegin->format('d.m.Y'),
                    'event_begin can not be in past'
                ),
                FlashMessage::ERROR
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
        if (isset($event['images']) && is_array($event['images'])) {
            foreach ($event['images'] as $image) {
                if (!is_array($image)) {
                    $this->addMessage(
                        sprintf(
                            'Event: %s - Date: %s - Error: %s',
                            $event['title'],
                            $eventBegin->format('d.m.Y'),
                            'Image must be of type array'
                        ),
                        FlashMessage::ERROR
                    );
                    return false;
                }
                if (!isset($image['url']) || empty(trim($image['url']))) {
                    $this->addMessage(
                        sprintf(
                            'Event: %s - Date: %s - Error: %s',
                            $event['title'],
                            $eventBegin->format('d.m.Y'),
                            'Array key "url" of image must be set and can not be empty'
                        ),
                        FlashMessage::ERROR
                    );
                    return false;
                }
                if (!filter_var($image['url'], FILTER_VALIDATE_URL)) {
                    $this->addMessage(
                        sprintf(
                            'Event: %s - Date: %s - Error: %s',
                            $event['title'],
                            $eventBegin->format('d.m.Y'),
                            'Image path has to be a valid URL'
                        ),
                        FlashMessage::ERROR
                    );
                    return false;
                }
            }
        }

        return true;
    }

    protected function addNotFoundMessage(array $event, string $property, string $value, \DateTime $date): void
    {
        $this->addMessage(
            sprintf(
                'Event: %s - Date: %s - Error: %s',
                $event['title'],
                $date->format('d.m.Y'),
                sprintf(
                    'Given %s "%s" does not exist in our database',
                    $property,
                    $value
                )
            ),
            FlashMessage::ERROR
        );
    }

    protected function areOrganizersProcessable(array $event): bool
    {
        if ($this->extConf->getOrganizerIsRequired() === false) {
            return false;
        }

        // @deprecated, will be removed with events2 7.0.0
        if (array_key_exists('organizer', $event) && $event['organizer'] !== '') {
            return true;
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
                    $queryBuilder->createNamedParameter($title, \PDO::PARAM_STR)
                )
            )
            ->execute()
            ->fetch();

        return $organizer ?: [];
    }

    protected function isLocationProcessable(array $event): bool
    {
        return $this->extConf->getLocationIsRequired()
            && array_key_exists('location', $event)
            && $event['location'] !== '';
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
                    $queryBuilder->createNamedParameter($title, \PDO::PARAM_STR)
                )
            )
            ->execute()
            ->fetch();

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
                    $queryBuilder->createNamedParameter($title, \PDO::PARAM_STR)
                )
            )
            ->execute()
            ->fetch();

        return $category ?: [];
    }

    protected function addMessage(string $message, int $severity = FlashMessage::OK): void
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
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $severity = FlashMessage::ERROR;
        }

        // show messages in TYPO3 BE when started manually
        /** @var FlashMessage $flashMessage */
        $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, '', $severity);
        /** @var FlashMessageService $flashMessageService */
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }

    /**
     * Get LogFile
     * If it does not exists, we create a new one in same directory of import file
     *
     * @return AbstractFile
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
                    $folder->getCombinedIdentifier() . $this->logFileName
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
