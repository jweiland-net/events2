<?php

namespace JWeiland\Events2\Tests\Functional\DataHandling;

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
use JWeiland\Events2\Domain\Model\Location;
use JWeiland\Events2\Domain\Model\Organizer;
use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Service\DatabaseService;
use JWeiland\Events2\Service\DayRelationService;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Functional test for DataHandler
 */
class DataHandlerTest extends FunctionalTestCase
{
    /**
     * @var DayRepository
     */
    protected $dayRepository;

    /**
     * @var QuerySettingsInterface
     */
    protected $querySettings;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/events2',
        'typo3conf/ext/maps2'
    ];

    public function setUp()
    {
        parent::setUp();

        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->dayRepository = $this->objectManager->get(DayRepository::class);
        $this->querySettings = $this->objectManager->get(QuerySettingsInterface::class);
        $this->querySettings->setStoragePageIds([11, 40]);
        $this->dayRepository->setDefaultQuerySettings($this->querySettings);
        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $dayRelationService = $this->objectManager->get(DayRelationService::class);
        $eventRepository = $this->objectManager->get(EventRepository::class);
        $eventRepository->setDefaultQuerySettings($this->querySettings);

        $this->importDataSet(__DIR__ . '/../Fixtures/pages.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/be_groups.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/be_users.xml');

        $organizer = new Organizer();
        $organizer->setPid(11);
        $organizer->setOrganizer('Stefan');

        $location = new Location();
        $location->setPid(11);
        $location->setLocation('Market');

        $eventBegin = new \DateTime('midnight');
        $eventBegin->modify('first day of this month')->modify('+4 days')->modify('-2 months');

        $event = new Event();
        $event->setPid(11);
        $event->setEventType('recurring');
        $event->setTopOfList(false);
        $event->setTitle('Week market');
        $event->setTeaser('');
        $event->setEventBegin($eventBegin);
        $event->setXth(31);
        $event->setWeekday(16);
        $event->setEachWeeks(0);
        $event->setEachMonths(0);
        $event->setRecurringEnd(null);
        $event->setFreeEntry(false);
        $event->setOrganizer($organizer);
        $event->setLocation($location);
        $persistenceManager->add($event);

        $persistenceManager->persistAll();

        $events = $eventRepository->findAll();
        foreach ($events as $event) {
            $dayRelationService->createDayRelations($event->getUid());
        }
    }

    public function tearDown()
    {
        unset($this->dayRepository);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function deleteEventByAdminWillRemoveDayRecords()
    {
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);
        $backendUser = new BackendUserAuthentication();
        $backendUser->user = [
            'uid' => 1,
            'admin' => 1
        ];

        $dataHandler = new DataHandler();
        $dataHandler->admin = 1;
        $dataHandler->start(
            [],
            [
                'tx_events2_domain_model_event' => [
                    1 => [
                        'delete' => 1
                    ]
                ]
            ],
            $backendUser
        );
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        $eventBegin = new \DateTime('today midnight');
        $eventBegin->modify('first day of this month');
        $eventEnd = new \DateTime('today midnight');
        $eventEnd->modify('last day of this month');

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_events2_domain_model_day');
        $queryBuilder->getRestrictions()->removeAll();
        $amountOfDeletedDays = $queryBuilder
            ->count('*')
            ->from('tx_events2_domain_model_day')
            ->where(
                $queryBuilder->expr()->gte(
                    'day',
                    $queryBuilder->createNamedParameter($eventBegin->format('U'), \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->lt(
                    'day',
                    $queryBuilder->createNamedParameter($eventEnd->format('U'), \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn(0);

        $this->assertSame(
            0,
            $amountOfDeletedDays
        );
    }

    /**
     * @test
     */
    public function deleteEventByEditorWillRemoveDayRecords()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('be_users');
        $user = $queryBuilder
            ->select('*')
            ->from('be_users')
            ->where('uid=2')
            ->execute()
            ->fetch();

        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['BE_USER']->user = $user;
        $GLOBALS['BE_USER']->fetchGroupData();

        $dataHandler = new DataHandler();
        $dataHandler->admin = 0;
        $dataHandler->start(
            [],
            [
                'tx_events2_domain_model_event' => [
                    1 => [
                        'delete' => 1
                    ]
                ]
            ]
        );
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        $eventBegin = new \DateTime('today midnight');
        $eventBegin->modify('first day of this month');
        $eventEnd = new \DateTime('today midnight');
        $eventEnd->modify('last day of this month');

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_events2_domain_model_day');
        $queryBuilder->getRestrictions()->removeAll();
        $amountOfDeletedDays = $queryBuilder
            ->count('*')
            ->from('tx_events2_domain_model_day')
            ->where(
                $queryBuilder->expr()->gte(
                    'day',
                    $queryBuilder->createNamedParameter($eventBegin->format('U'), \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->lt(
                    'day',
                    $queryBuilder->createNamedParameter($eventEnd->format('U'), \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn(0);

        $this->assertSame(
            0,
            $amountOfDeletedDays
        );
    }
}
