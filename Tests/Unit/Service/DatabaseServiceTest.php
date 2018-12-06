<?php

namespace JWeiland\Events2\Tests\Unit\Utility;

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
use Doctrine\DBAL\Statement;
use JWeiland\Events2\Service\DatabaseService;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case.
 */
class DatabaseServiceTest extends UnitTestCase
{
    /**
     * @var DatabaseService
     */
    protected $subject;

    /**
     * set up.
     */
    public function setUp()
    {
        $this->subject = new DatabaseService();
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
    public function getDaysInRangeWithoutStoragePidsAndWithoutCategories()
    {
        $startDate = new \DateTime('now');
        $endDate = new \DateTime('tomorrow');

        /** @var Statement|ObjectProphecy $statement */
        $statement = $this->prophesize(Statement::class);
        /** @var ExpressionBuilder|ObjectProphecy $expressionBuilder */
        $expressionBuilder = $this->prophesize(ExpressionBuilder::class);
        /** @var QueryBuilder|ObjectProphecy $queryBuilder */
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        /** @var ConnectionPool|ObjectProphecy $connectionPool */
        $connectionPool = $this->prophesize(ConnectionPool::class);

        $statement->fetchAll()->shouldBeCalled()->willReturn([]);

        $queryBuilder->quoteIdentifier('event.uid')->shouldBeCalled()->willReturn('`event.uid`');
        $queryBuilder->createNamedParameter($startDate->format('U'), 1)->shouldBeCalled()->willReturn(':startDate');
        $queryBuilder->createNamedParameter($endDate->format('U'), 1)->shouldBeCalled()->willReturn(':endDate');

        $expressionBuilder->eq('day.event', '`event.uid`')->shouldBeCalled();
        $expressionBuilder->gte('day.day', ':startDate')->shouldBeCalled();
        $expressionBuilder->lt('day.day', ':endDate')->shouldBeCalled();

        $queryBuilder->select('event.uid', 'event.title', 'day.day')->shouldBeCalled()->willReturn($queryBuilder->reveal());
        $queryBuilder->from('tx_events2_domain_model_day', 'day')->shouldBeCalled()->willReturn($queryBuilder->reveal());
        $queryBuilder->expr()->shouldBeCalled()->willReturn($expressionBuilder->reveal());
        $queryBuilder
            ->leftJoin(
                'day',
                'tx_events2_domain_model_event',
                'event',
                Argument::cetera()
            )
            ->shouldBeCalled()
            ->willReturn($queryBuilder->reveal());


        $queryBuilder->where(Argument::cetera())->shouldBeCalled()->willReturn($queryBuilder->reveal());
        $queryBuilder->expr(Argument::cetera())->shouldBeCalled()->willReturn($expressionBuilder->reveal());
        $queryBuilder->execute(Argument::cetera())->shouldBeCalled()->willReturn($statement->reveal());

        $connectionPool->getQueryBuilderForTable('tx_events2_domain_model_day')->shouldBeCalled()->willReturn($queryBuilder);

        GeneralUtility::addInstance(ConnectionPool::class, $connectionPool->reveal());

        $this->subject->getDaysInRange(
            $startDate,
            $endDate
        );
    }

    /**
     * @test
     */
    public function getDaysInRangeWithStoragePidsButWithoutCategories()
    {
        $startDate = new \DateTime('now');
        $endDate = new \DateTime('tomorrow');

        /** @var Statement|ObjectProphecy $statement */
        $statement = $this->prophesize(Statement::class);
        /** @var ExpressionBuilder|ObjectProphecy $expressionBuilder */
        $expressionBuilder = $this->prophesize(ExpressionBuilder::class);
        /** @var QueryBuilder|ObjectProphecy $queryBuilder */
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        /** @var ConnectionPool|ObjectProphecy $connectionPool */
        $connectionPool = $this->prophesize(ConnectionPool::class);

        $statement->fetchAll()->shouldBeCalled()->willReturn([]);

        $queryBuilder->quoteIdentifier('event.uid')->shouldBeCalled()->willReturn('`event.uid`');
        $queryBuilder->createNamedParameter([21, 22, 23], 101)->shouldBeCalled()->willReturn(':storagePids');
        $queryBuilder->createNamedParameter($startDate->format('U'), 1)->shouldBeCalled()->willReturn(':startDate');
        $queryBuilder->createNamedParameter($endDate->format('U'), 1)->shouldBeCalled()->willReturn(':endDate');

        $expressionBuilder->eq('day.event', '`event.uid`')->shouldBeCalled();
        $expressionBuilder->in('event.pid', ':storagePids')->shouldBeCalled();
        $expressionBuilder->gte('day.day', ':startDate')->shouldBeCalled();
        $expressionBuilder->lt('day.day', ':endDate')->shouldBeCalled();

        $queryBuilder->select('event.uid', 'event.title', 'day.day')->shouldBeCalled()->willReturn($queryBuilder->reveal());
        $queryBuilder->from('tx_events2_domain_model_day', 'day')->shouldBeCalled()->willReturn($queryBuilder->reveal());
        $queryBuilder->expr()->shouldBeCalled()->willReturn($expressionBuilder->reveal());
        $queryBuilder
            ->leftJoin(
                'day',
                'tx_events2_domain_model_event',
                'event',
                Argument::cetera()
            )
            ->shouldBeCalled()
            ->willReturn($queryBuilder->reveal());

        $queryBuilder->where(Argument::cetera())->shouldBeCalled()->willReturn($queryBuilder->reveal());
        $queryBuilder->expr(Argument::cetera())->shouldBeCalled()->willReturn($expressionBuilder->reveal());
        $queryBuilder->execute(Argument::cetera())->shouldBeCalled()->willReturn($statement->reveal());

        $connectionPool->getQueryBuilderForTable('tx_events2_domain_model_day')->shouldBeCalled()->willReturn($queryBuilder);

        GeneralUtility::addInstance(ConnectionPool::class, $connectionPool->reveal());

        $this->subject->getDaysInRange(
            $startDate,
            $endDate,
            [21, 22, 23]
        );
    }

    /**
     * @test
     */
    public function getDaysInRangeWithStoragePidsAndCategories()
    {
        $startDate = new \DateTime('now');
        $endDate = new \DateTime('tomorrow');

        /** @var Statement|ObjectProphecy $statement */
        $statement = $this->prophesize(Statement::class);
        /** @var ExpressionBuilder|ObjectProphecy $expressionBuilder */
        $expressionBuilder = $this->prophesize(ExpressionBuilder::class);
        /** @var QueryBuilder|ObjectProphecy $queryBuilder */
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        /** @var ConnectionPool|ObjectProphecy $connectionPool */
        $connectionPool = $this->prophesize(ConnectionPool::class);

        $statement->fetchAll()->shouldBeCalled()->willReturn([]);

        $queryBuilder->quoteIdentifier('event.uid')->shouldBeCalled()->willReturn('`event.uid`');
        $queryBuilder->quoteIdentifier('category_mm.uid_foreign')->shouldBeCalled()->willReturn('`category_mm.uid_foreign`');
        $queryBuilder->createNamedParameter([21, 22, 23], 101)->shouldBeCalled()->willReturn(':storagePids');
        $queryBuilder->createNamedParameter('tx_events2_domain_model_event', 2)->shouldBeCalled()->willReturn(':eventTable');
        $queryBuilder->createNamedParameter('categories', 2)->shouldBeCalled()->willReturn(':eventFieldname');
        $queryBuilder->createNamedParameter([31, 32, 33], 101)->shouldBeCalled()->willReturn(':categories');
        $queryBuilder->createNamedParameter($startDate->format('U'), 1)->shouldBeCalled()->willReturn(':startDate');
        $queryBuilder->createNamedParameter($endDate->format('U'), 1)->shouldBeCalled()->willReturn(':endDate');

        $expressionBuilder->eq('day.event', '`event.uid`')->shouldBeCalled();
        $expressionBuilder->in('event.pid', ':storagePids')->shouldBeCalled();
        $expressionBuilder->eq('event.uid', '`category_mm.uid_foreign`')->shouldBeCalled();
        $expressionBuilder->eq('category_mm.tablenames', ':eventTable')->shouldBeCalled();
        $expressionBuilder->eq('category_mm.fieldname', ':eventFieldname')->shouldBeCalled();
        $expressionBuilder->andX('', '', '')->shouldBeCalled()->willReturn(new CompositeExpression('AND'));
        $expressionBuilder->in('category_mm.uid_local', ':categories')->shouldBeCalled();
        $expressionBuilder->gte('day.day', ':startDate')->shouldBeCalled();
        $expressionBuilder->lt('day.day', ':endDate')->shouldBeCalled();

        $queryBuilder->select('event.uid', 'event.title', 'day.day')->shouldBeCalled()->willReturn($queryBuilder->reveal());
        $queryBuilder->from('tx_events2_domain_model_day', 'day')->shouldBeCalled()->willReturn($queryBuilder->reveal());
        $queryBuilder->expr()->shouldBeCalled()->willReturn($expressionBuilder->reveal());

        $queryBuilder
            ->leftJoin(
                'day',
                'tx_events2_domain_model_event',
                'event',
                Argument::cetera()
            )
            ->shouldBeCalled()
            ->willReturn($queryBuilder->reveal());
        $queryBuilder
            ->leftJoin(
                'event',
                'sys_category_record_mm',
                'category_mm',
                Argument::cetera()
            )
            ->shouldBeCalled()
            ->willReturn($queryBuilder->reveal());

        $queryBuilder->where(Argument::cetera())->shouldBeCalled()->willReturn($queryBuilder->reveal());
        $queryBuilder->expr(Argument::cetera())->shouldBeCalled()->willReturn($expressionBuilder->reveal());
        $queryBuilder->execute(Argument::cetera())->shouldBeCalled()->willReturn($statement->reveal());

        $connectionPool->getQueryBuilderForTable('tx_events2_domain_model_day')->shouldBeCalled()->willReturn($queryBuilder);

        GeneralUtility::addInstance(ConnectionPool::class, $connectionPool->reveal());

        $this->subject->getDaysInRange(
            $startDate,
            $endDate,
            [21, 22, 23],
            [31, 32, 33]
        );
    }
}
