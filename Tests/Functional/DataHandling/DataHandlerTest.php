<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\DataHandling;

use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Location;
use JWeiland\Events2\Domain\Model\Organizer;
use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Service\DayRelationService;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for DataHandler
 */
class DataHandlerTest extends FunctionalTestCase
{
    protected DayRepository $dayRepository;

    protected QuerySettingsInterface $querySettings;

    protected array $coreExtensionsToLoad = [
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'sjbr/static-info-tables',
        'jweiland/maps2',
        'jweiland/events2',
    ];

    protected function setUp(): void
    {
        self::markTestIncomplete('DataHandlerTest not updated until right now');

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

        $eventBegin = new \DateTimeImmutable('midnight');
        $eventBegin->modify('first day of this month')->modify('+4 days')->modify('-2 months');

        $event = GeneralUtility::makeInstance(Event::class);
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
        $event->addOrganizer($organizer);
        $event->setLocation($location);

        $persistenceManager->add($event);

        $persistenceManager->persistAll();

        $events = $eventRepository->findAll();
        foreach ($events as $event) {
            $dayRelationService->createDayRelations($event->getUid());
        }
    }

    protected function tearDown(): void
    {
        unset(
            $this->dayRepository,
        );

        parent::tearDown();
    }

    /**
     * @test
     */
    public function deleteEventByAdminWillRemoveDayRecords(): void
    {
        $this->setUpBackendUserFromFixture(1);
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);

        $dataHandler = new DataHandler();
        $dataHandler->start(
            [],
            [
                'tx_events2_domain_model_event' => [
                    1 => [
                        'delete' => 1,
                    ],
                ],
            ],
        );
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        $eventBegin = new \DateTimeImmutable('today midnight');
        $eventBegin->modify('first day of this month');

        $eventEnd = new \DateTimeImmutable('today midnight');
        $eventEnd->modify('last day of this month');

        $amountOfDeletedDays = $this->getDatabaseConnection()->selectCount(
            '*',
            'tx_events2_domain_model_day',
            sprintf(
                'day >= %d AND day < %d',
                $eventBegin->format('U'),
                $eventEnd->format('U'),
            ),
        );

        self::assertSame(
            0,
            $amountOfDeletedDays,
        );
    }

    /**
     * @test
     */
    public function deleteEventByEditorWillRemoveDayRecords(): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('be_users');
        $user = $queryBuilder
            ->select('*')
            ->from('be_users')
            ->where('uid=2')
            ->executeQuery()
            ->fetchAssociative();

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
                        'delete' => 1,
                    ],
                ],
            ],
        );
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        $eventBegin = new \DateTimeImmutable('today midnight');
        $eventBegin->modify('first day of this month');

        $eventEnd = new \DateTimeImmutable('today midnight');
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
                    $queryBuilder->createNamedParameter($eventBegin->format('U'), Connection::PARAM_INT),
                ),
                $queryBuilder->expr()->lt(
                    'day',
                    $queryBuilder->createNamedParameter($eventEnd->format('U'), Connection::PARAM_INT),
                ),
            )
            ->executeQuery()
            ->fetchOne();

        self::assertSame(
            0,
            $amountOfDeletedDays,
        );
    }
}
