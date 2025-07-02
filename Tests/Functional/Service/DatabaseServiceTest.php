<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Service;

use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Location;
use JWeiland\Events2\Domain\Model\Organizer;
use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Service\DatabaseService;
use JWeiland\Events2\Service\DayRelationService;
use JWeiland\Events2\Utility\DateTimeUtility;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for DatabaseService
 */
class DatabaseServiceTest extends FunctionalTestCase
{
    protected DayRepository $dayRepository;

    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'sjbr/static-info-tables',
        'jweiland/events2',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['BE_USER'] = new BackendUserAuthentication();

        $this->dayRepository = GeneralUtility::makeInstance(DayRepository::class);

        $querySettings = GeneralUtility::makeInstance(QuerySettingsInterface::class);
        $querySettings->setStoragePageIds([11, 40]);

        $this->dayRepository->setDefaultQuerySettings($querySettings);
        $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
        $dayRelationService = GeneralUtility::makeInstance(DayRelationService::class);

        $eventRepository = GeneralUtility::makeInstance(EventRepository::class);
        $eventRepository->setDefaultQuerySettings($querySettings);

        $organizer = new Organizer();
        $organizer->setPid(11);
        $organizer->setOrganizer('Stefan');

        $location = new Location();
        $location->setPid(11);
        $location->setLocation('Market');

        $eventBegin = new \DateTimeImmutable('first day of this month midnight');
        $eventBegin = $eventBegin
            ->modify('+4 days')
            ->modify('-2 months');

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

    #[Test]
    public function getDaysInRangeWillFindDaysForCurrentMonth(): void
    {
        $eventBegin = new \DateTimeImmutable('first day of this month midnight');
        $eventEnd = new \DateTimeImmutable('last day of this month midnight');

        $databaseService = new DatabaseService(
            new ExtConf(
                recurringPast: 3,
                recurringFuture: 6,
            ),
            new DateTimeUtility(),
        );

        $days = $databaseService->getDaysInRange($eventBegin, $eventEnd, [11]);

        self::assertGreaterThanOrEqual(
            3,
            count($days),
        );
    }
}
