<?php

namespace JWeiland\Events2\Tests\Unit\Domain\Repository;

/*
 * This file is part of the TYPO3 CMS project.
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
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Filter;
use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Persistence\Typo376\Generic\Query;
use JWeiland\Events2\Utility\DateTimeUtility;
use Prophecy\Argument;
use Prophecy\Promise\ReturnPromise;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Fluid\Core\ViewHelper\Arguments;

/**
 * Test case.
 *
 * @author Stefan Froemken <projects@jweiland.net>
 */
class DayRepositoryTest extends UnitTestCase
{
    /**
     * @var DayRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject;

    /**
     * @var DateTimeUtility
     */
    protected $dateTimeUtility;

    /**
     * @var Query|ObjectProphecy
     */
    protected $query;

    /**
     * @var QueryResult|ObjectProphecy
     */
    protected $queryResult;

    /**
     * @var DataMapper|ObjectProphecy
     */
    protected $dataMapper;

    /**
     * @var PersistenceManager|ObjectProphecy
     */
    protected $persistenceManager;

    /**
     * set up.
     */
    public function setUp()
    {
        $this->dateTimeUtility = new DateTimeUtility();
        $this->query = $this->prophesize(Query::class);
        $this->queryResult = $this->prophesize(QueryResult::class);
        $this->dataMapper = $this->prophesize(DataMapper::class);
        $this->persistenceManager = $this->prophesize(PersistenceManager::class);

        $this->queryResult->getQuery()->willReturn($this->query->reveal());

        $this->subject = $this->getMock(DayRepository::class, ['createQuery'], [], '', false);
        $this->subject->injectDateTimeUtility($this->dateTimeUtility);
        $this->subject
            ->method('createQuery')
            ->willReturn($this->query->reveal());
    }

    /**
     * tear down.
     */
    public function tearDown()
    {
        unset($this->subject);
    }

    /**
     * @test
     */
    public function findEventsWithEmptyTypeJumpsInListCase()
    {
        $this->query->logicalAnd(Argument::cetera())->shouldBeCalled()->willReturn([]);
        $this->query->greaterThanOrEqual(Argument::cetera())->shouldBeCalled();
        $this->query->getQuerySettings(Argument::cetera())->shouldBeCalled()->willReturn(new Typo3QuerySettings());
        $this->query->in(Argument::exact('pid'), Argument::any())->shouldBeCalled();
        $this->query->in(Argument::exact('event.pid'), Argument::any())->shouldBeCalled();
        $this->query->matching(Argument::exact([]))->shouldBeCalled()->willReturn($this->query->reveal());
        $this->query->setGroupings(Argument::cetera())->shouldBeCalled();
        $this->query->execute(Argument::cetera())->shouldBeCalled();

        $this->subject->findEvents('', new Filter());
    }

    /**
     * @test
     */
    public function groupDaysByEventAndSortReturnsEmptyArray()
    {
        $this->query
            ->setLimit(Argument::exact(15))
            ->shouldBeCalled()
            ->willReturn($this->query->reveal());
        $this->query
            ->setOffset(Argument::exact(0))
            ->shouldBeCalled()
            ->willReturn($this->query->reveal());
        $this->query
            ->execute()
            ->shouldBeCalled()
            ->willReturn($this->queryResult->reveal());

        $this->queryResult
            ->count()
            ->shouldBeCalled()
            ->willReturn(0);

        $this->assertSame(
            [],
            $this->subject->groupDaysByEventAndSort($this->queryResult->reveal(), 7)
        );
    }

    /**
     * @test
     */
    public function findByDayWillMergeEvents()
    {
        $day = 32415;
        $this->queryResult->getFirst()->shouldBeCalled();

        $this->query->equals(Argument::exact('uid'), Argument::exact($day))->shouldBeCalled();
        $this->query->matching(Argument::cetera())->shouldBeCalled()->willReturn($this->query->reveal());
        $this->query->setGroupings(Argument::exact(['event', 'sortDayTime']))->shouldBeCalled();
        $this->query->execute(Argument::cetera())->shouldBeCalled()->willReturn($this->queryResult->reveal());

        $this->subject->findByDay($day);
    }

    /**
     * @test
     */
    public function findByTimestampWillNotAddCategories()
    {
        $timestamp = 12345678;

        $this->query->getQuerySettings(Argument::cetera())->shouldBeCalled()->willReturn(new Typo3QuerySettings());
        $this->query->in(Argument::exact('pid'), Argument::any())->shouldBeCalled();
        $this->query->in(Argument::exact('event.pid'), Argument::any())->shouldBeCalled();
        $this->query->in('event.categories.uid')->shouldNotBeCalled();
        $this->query->equals(Argument::exact('day'), Argument::exact($timestamp))->shouldBeCalled();
        $this->query->logicalAnd(Argument::cetera())->shouldBeCalled();
        $this->query->matching(Argument::cetera())->shouldBeCalled()->willReturn($this->query->reveal());
        $this->query->setGroupings(Argument::cetera())->shouldBeCalled();
        $this->query->execute(Argument::cetera())->shouldBeCalled();

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

        $this->query->getQuerySettings(Argument::cetera())->shouldBeCalled()->willReturn(new Typo3QuerySettings());
        $this->query->in(Argument::exact('pid'), Argument::any())->shouldBeCalled();
        $this->query->in(Argument::exact('event.pid'), Argument::any())->shouldBeCalled();
        $this->query->in('event.categories.uid', Argument::exact([12, 654, 2435]))->shouldBeCalled();
        $this->query->equals(Argument::exact('day'), Argument::exact($timestamp))->shouldBeCalled();
        $this->query->logicalAnd(Argument::cetera())->shouldBeCalled();
        $this->query->matching(Argument::cetera())->shouldBeCalled()->willReturn($this->query->reveal());
        $this->query->setGroupings(Argument::cetera())->shouldBeCalled();
        $this->query->execute(Argument::cetera())->shouldBeCalled();

        $this->subject->setSettings($settings);
        $this->subject->findByTimestamp($timestamp);
    }

    /**
     * @test
     */
    public function findByTimestampWillMergeEvents()
    {
        $timestamp = 12345678;

        $this->query->getQuerySettings(Argument::cetera())->shouldBeCalled()->willReturn(new Typo3QuerySettings());
        $this->query->in(Argument::exact('pid'), Argument::any())->shouldBeCalled();
        $this->query->in(Argument::exact('event.pid'), Argument::any())->shouldBeCalled();
        $this->query->equals(Argument::exact('day'), Argument::exact($timestamp))->shouldBeCalled();
        $this->query->logicalAnd(Argument::cetera())->shouldBeCalled();
        $this->query->matching(Argument::cetera())->shouldBeCalled()->willReturn($this->query->reveal());
        $this->query->setGroupings(Argument::exact(['event', 'sortDayTime']))->shouldBeCalled();
        $this->query->execute(Argument::cetera())->shouldBeCalled();

        $this->subject->findByTimestamp($timestamp);
    }

    /**
     * @test
     */
    public function findOneByTimestampWithEventAndTimestamp()
    {
        $event = 24;
        $timestamp = 12345678;

        $this->queryResult->getFirst()->shouldBeCalled();

        $this->query->getQuerySettings(Argument::cetera())->shouldBeCalled()->willReturn(new Typo3QuerySettings());
        $this->query->in(Argument::exact('pid'), Argument::any())->shouldBeCalled();
        $this->query->in(Argument::exact('event.pid'), Argument::any())->shouldBeCalled();
        $this->query->equals(Argument::exact('event'), Argument::exact($event))->shouldBeCalled();
        $this->query->equals(Argument::exact('dayTime'), Argument::exact($timestamp))->shouldBeCalled();
        $this->query->logicalAnd(Argument::cetera())->shouldBeCalled();
        $this->query->matching(Argument::cetera())->shouldBeCalled()->willReturn($this->query->reveal());
        $this->query->execute(Argument::cetera())->shouldBeCalled()->willReturn($this->queryResult->reveal());

        $this->subject->findOneByTimestamp($event, $timestamp);
    }

    /**
     * @test
     */
    public function findOneByTimestampWithEventAndNoTimestampWillChangeOrdering()
    {
        $event = 24;
        $timestamp = 0;

        $this->queryResult->getFirst()->shouldBeCalled();

        $this->query->getQuerySettings(Argument::cetera())->shouldBeCalled()->willReturn(new Typo3QuerySettings());
        $this->query->in(Argument::exact('pid'), Argument::any())->shouldBeCalled();
        $this->query->in(Argument::exact('event.pid'), Argument::any())->shouldBeCalled();
        $this->query->equals(Argument::exact('event'), Argument::exact($event))->shouldBeCalled();
        $this->query->greaterThanOrEqual(Argument::exact('dayTime'), Argument::any())->shouldBeCalled();
        $this->query->setOrderings(Argument::exact(['dayTime' => 'ASC']))->shouldBeCalled();
        $this->query->logicalAnd(Argument::cetera())->shouldBeCalled();
        $this->query->matching(Argument::cetera())->shouldBeCalled()->willReturn($this->query->reveal());
        $this->query->execute(Argument::cetera())->shouldBeCalled()->willReturn($this->queryResult->reveal());

        $this->subject->findOneByTimestamp($event, $timestamp);
    }
}
