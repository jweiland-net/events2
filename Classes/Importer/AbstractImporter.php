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
use JWeiland\Events2\Helper\PathSegmentHelper;
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
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
    protected int $storagePid = 0;

    /**
     * The file to import
     */
    protected FileInterface $file;

    protected string $logFileName = 'Messages.txt';

    protected array $allowedMimeType = [];

    protected PersistenceManagerInterface $persistenceManager;

    protected EventRepository $eventRepository;

    protected OrganizerRepository $organizerRepository;

    protected LocationRepository $locationRepository;

    protected CategoryRepository $categoryRepository;

    protected PathSegmentHelper $pathSegmentHelper;

    protected DateTimeUtility $dateTimeUtility;

    protected ExtConf $extConf;

    protected \DateTimeImmutable $today;

    public function __construct(
        EventRepository $eventRepository,
        OrganizerRepository $organizerRepository,
        LocationRepository $locationRepository,
        CategoryRepository $categoryRepository,
        PersistenceManagerInterface $persistenceManager,
        PathSegmentHelper $pathSegmentHelper,
        DateTimeUtility $dateTimeUtility,
        ExtConf $extConf
    ) {
        $this->eventRepository = $eventRepository;
        $this->organizerRepository = $organizerRepository;
        $this->locationRepository = $locationRepository;
        $this->categoryRepository = $categoryRepository;
        $this->persistenceManager = $persistenceManager;
        $this->pathSegmentHelper = $pathSegmentHelper;
        $this->dateTimeUtility = $dateTimeUtility;
        $this->extConf = $extConf;
        $this->today = new \DateTimeImmutable('now');
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
            $this->addMessage('MimeType of file is not allowed', AbstractMessage::ERROR);
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
                    'event_begin can not be in past'
                ),
                AbstractMessage::ERROR
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
                        'Image must be of type array'
                    ),
                    AbstractMessage::ERROR
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
                    AbstractMessage::ERROR
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
                    AbstractMessage::ERROR
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
                    $value
                )
            ),
            AbstractMessage::ERROR
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
                    $queryBuilder->createNamedParameter($title)
                )
            )
            ->execute()
            ->fetch(\PDO::FETCH_ASSOC);

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
                    $queryBuilder->createNamedParameter($title)
                )
            )
            ->execute()
            ->fetch(\PDO::FETCH_ASSOC);

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
                    $queryBuilder->createNamedParameter($title)
                )
            )
            ->execute()
            ->fetch(\PDO::FETCH_ASSOC);

        return $category ?: [];
    }

    protected function addMessage(string $message, int $severity = AbstractMessage::OK): void
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
            $severity = AbstractMessage::ERROR;
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
