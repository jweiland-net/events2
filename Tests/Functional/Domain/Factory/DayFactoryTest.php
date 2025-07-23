<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Domain\Factory;

use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Domain\Factory\DayFactory;
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Service\DatabaseService;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use JWeiland\Events2\Tests\Functional\Traits\InsertEventTrait;
use JWeiland\Events2\Utility\DateTimeUtility;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryFactory;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for DayFactory
 */
class DayFactoryTest extends FunctionalTestCase
{
    use InsertEventTrait;

    protected DayFactory $subject;

    protected Query $query;

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

        $querySettings = $this->get(QuerySettingsInterface::class);
        $querySettings->setStoragePageIds([Events2Constants::PAGE_STORAGE]);

        $dayRepository = $this->get(DayRepository::class);
        $dayRepository->setDefaultQuerySettings($querySettings);

        $eventRepository = $this->get(EventRepository::class);
        $eventRepository->setDefaultQuerySettings($querySettings);

        $queryFactory = $this->get(QueryFactory::class);

        /** @var Query $query */
        $this->query = $queryFactory->create(Day::class);
        $this->query->setQuerySettings($querySettings);

        $this->subject = new DayFactory(
            new DatabaseService(new ExtConf(), new DateTimeUtility()),
            $dayRepository,
            $eventRepository,
            GeneralUtility::makeInstance(ConnectionPool::class),
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->dayRepository,
        );

        parent::tearDown();
    }

    #[Test]
    public function findDayWithTimestampWillReturnExactDay(): void
    {
        $date = new \DateTimeImmutable('today 07:30:00');

        $this->insertEvent(
            title: 'Exactly match',
            eventBegin: new \DateTimeImmutable('today midnight'),
            timeBegin: '07:30',
        );
        $this->createDayRelations();

        $day = $this->subject->findDayByEventAndTimestamp(
            1,
            (int)$date->format('U'),
            $this->query,
        );

        self::assertSame(
            (int)$date->format('U'),
            $day->getDayTimeAsTimestamp(),
        );
    }

    #[Test]
    public function findDayWithTimestampWillReturnNextDay(): void
    {
        $date = new \DateTimeImmutable('today 07:30:00');

        $this->insertEvent(
            title: 'Next match',
            eventBegin: new \DateTimeImmutable('today midnight'),
            timeBegin: '16:00',
        );
        $this->createDayRelations();

        $day = $this->subject->findDayByEventAndTimestamp(
            1,
            (int)$date->format('U'),
            $this->query,
        );

        self::assertSame(
            (int)(new \DateTimeImmutable('today 16:00:00'))->format('U'),
            $day->getDayTimeAsTimestamp(),
        );
    }

    #[Test]
    public function findDayWithTimestampWillReturnPreviousDay(): void
    {
        $date = new \DateTimeImmutable('today 07:30:00');

        $this->insertEvent(
            title: 'Previous match',
            eventBegin: new \DateTimeImmutable('today midnight'),
            timeBegin: '02:00',
        );
        $this->createDayRelations();

        $day = $this->subject->findDayByEventAndTimestamp(
            1,
            (int)$date->format('U'),
            $this->query,
        );

        self::assertSame(
            (int)(new \DateTimeImmutable('today 02:00:00'))->format('U'),
            $day->getDayTimeAsTimestamp(),
        );
    }

    #[Test]
    public function findDayWithTimestampWillReturnDynamicDay(): void
    {
        $date = new \DateTimeImmutable('today 07:30:00');

        // Insert an event outside the default range (-3 months, + 6 months)
        $this->insertEvent(
            title: 'Dynamic match',
            eventBegin: new \DateTimeImmutable('-1 year midnight'),
            timeBegin: '07:30',
        );
        $this->createDayRelations();

        $day = $this->subject->findDayByEventAndTimestamp(
            1,
            (int)$date->format('U'),
            $this->query,
        );

        self::assertSame(
            (int)(new \DateTimeImmutable('-1 year 07:30:00'))->format('U'),
            $day->getDayTimeAsTimestamp(),
        );
        self::assertNull(
            $day->getUid(),
        );
    }
}
