<?php
declare(strict_types = 1);
namespace JWeiland\Events2\Domain\Repository;

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
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\Generic\Session;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Repository to get and find event records
 */
class EventRepository extends Repository
{
    /**
     * @var array
     */
    protected $defaultOrderings = [
        'eventBegin' => QueryInterface::ORDER_ASCENDING,
    ];

    /**
     * @var DateTimeUtility
     */
    protected $dateTimeUtility;

    /**
     * @var DataMapper
     */
    protected $dataMapper;

    /**
     * @var Session
     */
    protected $persistenceSession;

    /**
     * @var ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * @var array
     */
    protected $settings = [];

    /**
     * @param DateTimeUtility $dateTimeUtility
     */
    public function injectDateTimeUtility(DateTimeUtility $dateTimeUtility)
    {
        $this->dateTimeUtility = $dateTimeUtility;
    }

    /**
     * @param DataMapper $dataMapper
     */
    public function injectDataMapper(DataMapper $dataMapper)
    {
        $this->dataMapper = $dataMapper;
    }

    /**
     * @param Session $persistenceSession
     */
    public function injectPersistenceSession(Session $persistenceSession)
    {
        $this->persistenceSession = $persistenceSession;
    }

    /**
     * @param ConfigurationManagerInterface $configurationManager
     */
    public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * Sets the settings
     *
     * @param array $settings
     */
    public function setSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Find event by uid whether it is hidden or not.
     * Do not add Event as strict_type as this method can also return null
     *
     * @param int $eventUid
     * @return Event|null
     */
    public function findHiddenEntryByUid(int $eventUid)
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setIgnoreEnableFields(true);
        $query->getQuerySettings()->setEnableFieldsToBeIgnored(['disabled']);
        $query->getQuerySettings()->setRespectStoragePage(false);

        /** @var Event $event */
        $event = $query->matching($query->equals('uid', (int)$eventUid))->execute()->getFirst();
        return $event;
    }

    /**
     * Find events of a specified user.
     *
     * @return QueryResultInterface
     */
    public function findMyEvents(): QueryResultInterface
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->objectManager->get(UserRepository::class);
        $organizer = (int)$userRepository->getFieldFromUser('tx_events2_organizer');
        $query = $this->createQuery();

        return $query->matching($query->equals('organizer.uid', $organizer))->execute();
    }
}
