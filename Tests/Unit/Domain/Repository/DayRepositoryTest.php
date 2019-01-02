<?php

namespace JWeiland\Events2\Tests\Unit\Domain\Repository;

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

use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Filter;
use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Persistence\Typo384\Generic\Query;
use JWeiland\Events2\Utility\DateTimeUtility;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * Test case.
 */
class DayRepositoryTest extends UnitTestCase
{
    /**
     * @var DayRepository
     */
    protected $subject;

    /**
     * @var DateTimeUtility
     */
    protected $dateTimeUtility;

    /**
     * @var ObjectManager|ObjectProphecy
     */
    protected $objectManagerProphecy;

    /**
     * @var Query|ObjectProphecy
     */
    protected $queryProphecy;

    /**
     * @var QueryResult|ObjectProphecy
     */
    protected $queryResultProphecy;

    /**
     * @var DataMapper|ObjectProphecy
     */
    protected $dataMapperProphecy;

    /**
     * @var PersistenceManager|ObjectProphecy
     */
    protected $persistenceManagerProphecy;

    /**
     * @var ExtConf|ObjectProphecy
     */
    protected $extConfProphecy;

    /**
     * set up.
     */
    public function setUp()
    {
        $this->dateTimeUtility = new DateTimeUtility();
        $this->objectManagerProphecy = $this->prophesize(ObjectManager::class);
        $this->queryResultProphecy = $this->prophesize(QueryResult::class);
        $this->dataMapperProphecy = $this->prophesize(DataMapper::class);
        $this->extConfProphecy = $this->prophesize(ExtConf::class);

        $this->queryProphecy = $this->prophesize(Query::class);
        $this->queryProphecy
            ->setOrderings([
                'event.topOfList' => QueryInterface::ORDER_DESCENDING,
                'sortDayTime' => QueryInterface::ORDER_ASCENDING,
                'dayTime' => QueryInterface::ORDER_ASCENDING
            ])->willReturn(null);

        $this->persistenceManagerProphecy = $this->prophesize(PersistenceManager::class);
        $this->persistenceManagerProphecy->createQueryForType(Day::class)->willReturn($this->queryProphecy->reveal());

        $this->subject = new DayRepository($this->objectManagerProphecy->reveal());
        $this->subject->injectDateTimeUtility($this->dateTimeUtility);
        $this->subject->injectExtConf($this->extConfProphecy->reveal());
        $this->subject->injectPersistenceManager($this->persistenceManagerProphecy->reveal());
    }

    /**
     * tear down.
     */
    public function tearDown()
    {
        unset($this->subject);
        unset(
            $this->dateTimeUtility,
            $this->objectManagerProphecy,
            $this->queryProphecy,
            $this->queryResultProphecy,
            $this->dataMapperProphecy,
            $this->persistenceManagerProphecy,
            $this->extConfProphecy
        );
    }

    /**
     * @test
     */
    public function findEventsWithEmptyTypeJumpsInListCase()
    {
        $this->extConfProphecy->getRecurringPast()->shouldBeCalled()->willReturn(3);

        $this->queryProphecy->logicalAnd(Argument::cetera())->shouldBeCalled()->willReturn([]);
        $this->queryProphecy->greaterThanOrEqual(Argument::cetera())->shouldBeCalled();
        $this->queryProphecy->getQuerySettings(Argument::cetera())->shouldBeCalled()->willReturn(new Typo3QuerySettings());
        $this->queryProphecy->in(Argument::exact('pid'), Argument::any())->shouldBeCalled();
        $this->queryProphecy->in(Argument::exact('event.pid'), Argument::any())->shouldBeCalled();
        $this->queryProphecy->matching(Argument::exact([]))->shouldBeCalled()->willReturn($this->queryProphecy->reveal());
        $this->queryProphecy->setGroupings(Argument::cetera())->shouldBeCalled();
        $this->queryProphecy->execute(Argument::cetera())->shouldBeCalled()->willReturn($this->queryResultProphecy->reveal());

        $this->subject->findEvents('', new Filter());
    }

    /**
     * @test
     */
    public function groupDaysByEventAndSortReturnsEmptyArray()
    {
        $this->queryResultProphecy->getQuery()->shouldBeCalled()->willReturn($this->queryProphecy->reveal());

        $this->queryProphecy
            ->setLimit(Argument::exact(15))
            ->shouldBeCalled()
            ->willReturn($this->queryProphecy->reveal());
        $this->queryProphecy
            ->setOffset(Argument::exact(0))
            ->shouldBeCalled()
            ->willReturn($this->queryProphecy->reveal());
        $this->queryProphecy
            ->execute()
            ->shouldBeCalled()
            ->willReturn($this->queryResultProphecy->reveal());

        $this->queryResultProphecy
            ->count()
            ->shouldBeCalled()
            ->willReturn(0);

        $this->assertSame(
            [],
            $this->subject->groupDaysByEventAndSort($this->queryResultProphecy->reveal(), 7)
        );
    }

    /**
     * @test
     */
    public function findByDayWillMergeEvents()
    {
        $day = 32415;
        $this->queryResultProphecy->getFirst()->shouldBeCalled()->willReturn(new Day());

        $this->queryProphecy->equals(Argument::exact('uid'), Argument::exact($day))->shouldBeCalled();
        $this->queryProphecy->matching(Argument::cetera())->shouldBeCalled()->willReturn($this->queryProphecy->reveal());
        $this->queryProphecy->setGroupings(Argument::exact(['event', 'sortDayTime']))->shouldBeCalled();
        $this->queryProphecy->execute(Argument::cetera())->shouldBeCalled()->willReturn($this->queryResultProphecy->reveal());

        $this->subject->findByDay($day);
    }

    /**
     * @test
     */
    public function findByTimestampWillNotAddCategories()
    {
        $timestamp = 12345678;

        $this->queryProphecy->getQuerySettings(Argument::cetera())->shouldBeCalled()->willReturn(new Typo3QuerySettings());
        $this->queryProphecy->in(Argument::exact('pid'), Argument::any())->shouldBeCalled();
        $this->queryProphecy->in(Argument::exact('event.pid'), Argument::any())->shouldBeCalled();
        $this->queryProphecy->in('event.categories.uid')->shouldNotBeCalled();
        $this->queryProphecy->equals(Argument::exact('day'), Argument::exact($timestamp))->shouldBeCalled();
        $this->queryProphecy->logicalAnd(Argument::cetera())->shouldBeCalled();
        $this->queryProphecy->matching(Argument::cetera())->shouldBeCalled()->willReturn($this->queryProphecy->reveal());
        $this->queryProphecy->setGroupings(Argument::cetera())->shouldBeCalled();
        $this->queryProphecy->execute(Argument::cetera())->shouldBeCalled()->willReturn($this->queryResultProphecy->reveal());

        $this->subject->findByTimestamp($timestamp);
    }

    /**
     * @test
     */
    public function findByTimestampWillAddCategories()
    {
        $timestamp = 12345678;
        $settings = [
            'categories' => '12 ,654 ,  2435'
        ];

        $this->queryProphecy->getQuerySettings(Argument::cetera())->shouldBeCalled()->willReturn(new Typo3QuerySettings());
        $this->queryProphecy->in(Argument::exact('pid'), Argument::any())->shouldBeCalled();
        $this->queryProphecy->in(Argument::exact('event.pid'), Argument::any())->shouldBeCalled();
        $this->queryProphecy->in('event.categories.uid', Argument::exact([12, 654, 2435]))->shouldBeCalled();
        $this->queryProphecy->equals(Argument::exact('day'), Argument::exact($timestamp))->shouldBeCalled();
        $this->queryProphecy->logicalAnd(Argument::cetera())->shouldBeCalled();
        $this->queryProphecy->matching(Argument::cetera())->shouldBeCalled()->willReturn($this->queryProphecy->reveal());
        $this->queryProphecy->setGroupings(Argument::cetera())->shouldBeCalled();
        $this->queryProphecy->execute(Argument::cetera())->shouldBeCalled()->willReturn($this->queryResultProphecy->reveal());

        $this->subject->setSettings($settings);
        $this->subject->findByTimestamp($timestamp);
    }

    /**
     * @test
     */
    public function findByTimestampWillMergeEvents()
    {
        $timestamp = 12345678;

        $this->queryProphecy->getQuerySettings(Argument::cetera())->shouldBeCalled()->willReturn(new Typo3QuerySettings());
        $this->queryProphecy->in(Argument::exact('pid'), Argument::any())->shouldBeCalled();
        $this->queryProphecy->in(Argument::exact('event.pid'), Argument::any())->shouldBeCalled();
        $this->queryProphecy->equals(Argument::exact('day'), Argument::exact($timestamp))->shouldBeCalled();
        $this->queryProphecy->logicalAnd(Argument::cetera())->shouldBeCalled();
        $this->queryProphecy->matching(Argument::cetera())->shouldBeCalled()->willReturn($this->queryProphecy->reveal());
        $this->queryProphecy->setGroupings(Argument::exact(['event', 'sortDayTime']))->shouldBeCalled();
        $this->queryProphecy->execute(Argument::cetera())->shouldBeCalled()->willReturn($this->queryResultProphecy->reveal());

        $this->subject->findByTimestamp($timestamp);
    }

    /**
     * @test
     */
    public function findOneByTimestampWithEventAndTimestamp()
    {
        $event = 24;
        $timestamp = 12345678;

        $this->queryResultProphecy->getFirst()->shouldBeCalled();

        $this->queryProphecy->getQuerySettings(Argument::cetera())->shouldBeCalled()->willReturn(new Typo3QuerySettings());
        $this->queryProphecy->in(Argument::exact('pid'), Argument::any())->shouldBeCalled();
        $this->queryProphecy->in(Argument::exact('event.pid'), Argument::any())->shouldBeCalled();
        $this->queryProphecy->equals(Argument::exact('event'), Argument::exact($event))->shouldBeCalled();
        $this->queryProphecy->equals(Argument::exact('dayTime'), Argument::exact($timestamp))->shouldBeCalled();
        $this->queryProphecy->logicalAnd(Argument::cetera())->shouldBeCalled();
        $this->queryProphecy->matching(Argument::cetera())->shouldBeCalled()->willReturn($this->queryProphecy->reveal());
        $this->queryProphecy->execute(Argument::cetera())->shouldBeCalled()->willReturn($this->queryResultProphecy->reveal());

        $this->subject->findOneByTimestamp($event, $timestamp);
    }

    /**
     * @test
     */
    public function findOneByTimestampWithEventAndNoTimestampWillChangeOrdering()
    {
        $event = 24;
        $timestamp = 0;

        $this->queryResultProphecy->getFirst()->shouldBeCalled();

        $this->queryProphecy->getQuerySettings(Argument::cetera())->shouldBeCalled()->willReturn(new Typo3QuerySettings());
        $this->queryProphecy->in(Argument::exact('pid'), Argument::any())->shouldBeCalled();
        $this->queryProphecy->in(Argument::exact('event.pid'), Argument::any())->shouldBeCalled();
        $this->queryProphecy->equals(Argument::exact('event'), Argument::exact($event))->shouldBeCalled();
        $this->queryProphecy->greaterThanOrEqual(Argument::exact('dayTime'), Argument::any())->shouldBeCalled();
        $this->queryProphecy->setOrderings(Argument::exact(['dayTime' => 'ASC']))->shouldBeCalled();
        $this->queryProphecy->logicalAnd(Argument::cetera())->shouldBeCalled();
        $this->queryProphecy->matching(Argument::cetera())->shouldBeCalled()->willReturn($this->queryProphecy->reveal());
        $this->queryProphecy->execute(Argument::cetera())->shouldBeCalled()->willReturn($this->queryResultProphecy->reveal());

        $this->subject->findOneByTimestamp($event, $timestamp);
    }
}
