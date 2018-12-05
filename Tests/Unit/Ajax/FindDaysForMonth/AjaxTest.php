<?php

namespace JWeiland\Events2\Tests\Unit\Ajax\FindDaysForMonth;

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

use Doctrine\DBAL\Driver\Statement;
use JWeiland\Events2\Ajax\FindDaysForMonth;
use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Tests\Unit\AbstractUnitTestCase;
use JWeiland\Events2\Utility\DateTimeUtility;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;

/**
 * Test case.
 */
class AjaxTest extends AbstractUnitTestCase
{
    /**
     * @var FindDaysForMonth\Ajax
     */
    protected $subject;

    /**
     * @var ExtConf|ObjectProphecy
     */
    protected $extConfProphecy;

    /**
     * @var DayRepository|ObjectProphecy
     */
    protected $dayRepositoryProphecy;

    /**
     * @var FrontendUserAuthentication|ObjectProphecy
     */
    protected $frontendUserAuthenticationProphecy;

    /**
     * @var QuerySettingsInterface|ObjectProphecy
     */
    protected $querySettingsProphecy;

    /**
     * set up.
     */
    public function setUp()
    {
        $GLOBALS['TYPO3_LOADED_EXT'] = [
            'events2' => []
        ];

        $this->extConfProphecy = $this->prophesize(ExtConf::class);
        $this->extConfProphecy->getRecurringPast()->willReturn(3);
        $this->extConfProphecy->getRecurringFuture()->willReturn(6);

        $this->frontendUserAuthenticationProphecy = $this->prophesize(FrontendUserAuthentication::class);
        $this->querySettingsProphecy = $this->prophesize(QuerySettingsInterface::class);

        $this->subject = new FindDaysForMonth\Ajax(
            $this->extConfProphecy->reveal(),
            new DateTimeUtility(),
            new CacheHashCalculator()
        );
    }

    /**
     * tear down.
     */
    public function tearDown()
    {
        unset($this->subject);
        unset($this->query);
        unset($this->dbProphecy);
    }

    /**
     * DataProvider for arguments
     *
     * @return array
     */
    public function dataProviderForProcessAjaxRequestForcesTooHighMonthAndYearInRange()
    {
        $arguments = [];
        $arguments['negative values'] = [['month' => -50, 'year' => -2400], ['month' => '01', 'year' => '1500'], 1];
        $arguments['valid int values'] = [['month' => 10, 'year' => 2100], ['month' => '10', 'year' => '2100'], 10];
        $arguments['string values'] = [['month' => '10', 'year' => '2100'], ['month' => '10', 'year' => '2100'], 10];
        $arguments['too huge values'] = [['month' => 250, 'year' => 12050], ['month' => '12', 'year' => '2500'], 12];
        $arguments['add 0 to small numbers'] = [['month' => 3, 'year' => 2145], ['month' => '03', 'year' => '2145'], 3];

        return $arguments;
    }

    /**
     * @test
     * @dataProvider dataProviderForProcessAjaxRequestForcesTooHighMonthAndYearInRange
     */
    public function processAjaxRequestForcesTooHighMonthAndYearInRange($arguments, $expectedArguments, $expectedMonth)
    {
        /** @var FrontendUserAuthentication|ObjectProphecy $frontendUserAuthentication */
        $frontendUserAuthentication = $this->prophesize(FrontendUserAuthentication::class);
        $frontendUserAuthentication->start()->shouldBeCalled();
        $frontendUserAuthentication->setAndSaveSessionData(
            'events2MonthAndYearForCalendar',
            [
                'month' => $expectedArguments['month'],
                'year' => $expectedArguments['year']
            ]
        )->shouldBeCalled();

        GeneralUtility::addInstance(FrontendUserAuthentication::class, $frontendUserAuthentication->reveal());

        $this->buildAssertionForDatabaseWithReturnValue(
            'tx_events2_domain_model_holiday',
            [],
            [
                [
                    'expr' => 'eq',
                    'field' => 'month',
                    'value' => $expectedMonth
                ]
            ]
        );

        $this->subject->processAjaxRequest($arguments);
    }

    /**
     * @test
     */
    public function processAjaxRequestWillGenerateTwoDayRecordsAsJson()
    {
        $currentDate = new \DateTime('now');
        $day = (int)$currentDate->format('j');
        $tomorrow = clone $currentDate;
        $tomorrow = $tomorrow->modify('+1 day')->format('j');
        $arguments = [
            'categories' => '10,11,12',
            'month' => $currentDate->format('n'),
            'year' => $currentDate->format('Y'),
            'pidOfListPage' => 4321,
            'storagePids' => '21,22,23'
        ];

        $day1 = new \DateTime('now');
        $day1 = $day1->format('U');
        $day2 = new \DateTime('tomorrow');
        $day2 = $day2->format('U');
        $days = [
            [
                'uid' => 456,
                'title' => 'Test123',
                'day' => $day1
            ],
            [
                'uid' => 654,
                'title' => 'Test321',
                'day' => $day2
            ]
        ];

        /** @var FrontendUserAuthentication|ObjectProphecy $frontendUserAuthentication */
        $frontendUserAuthentication = $this->prophesize(FrontendUserAuthentication::class);
        $frontendUserAuthentication->start()->shouldBeCalled();
        $frontendUserAuthentication->setAndSaveSessionData(Argument::cetera())->shouldBeCalled();
        GeneralUtility::addInstance(FrontendUserAuthentication::class, $frontendUserAuthentication->reveal());

        /** @var Statement|ObjectProphecy $statement */
        $statement = $this->prophesize(Statement::class);
        /** @var ExpressionBuilder|ObjectProphecy $expressionBuilder */
        $expressionBuilder = $this->prophesize(ExpressionBuilder::class);
        /** @var QueryBuilder|ObjectProphecy $queryBuilder */
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        /** @var ConnectionPool|ObjectProphecy $connectionPool */
        $connectionPool = $this->prophesize(ConnectionPool::class);

        $statement->fetchAll()->willReturn($days);

        $expressionBuilder->eq('day.event', Argument::cetera())->shouldBeCalled();
        $expressionBuilder->eq('event.uid', Argument::cetera())->shouldBeCalled();
        $expressionBuilder->eq('category_mm.tablenames', Argument::cetera())->shouldBeCalled();
        $expressionBuilder->eq('category_mm.fieldname', Argument::cetera())->shouldBeCalled();
        $expressionBuilder->andX(Argument::cetera())->shouldBeCalled()->willReturn(new CompositeExpression('AND'));

        $expressionBuilder->in('category_mm.uid_local', Argument::cetera())->shouldBeCalled();
        $expressionBuilder->in('event.pid', Argument::cetera())->shouldBeCalled();
        $expressionBuilder->gte('day.day', Argument::cetera())->shouldBeCalled();
        $expressionBuilder->lt('day.day', Argument::cetera())->shouldBeCalled();

        $queryBuilder->select('event.uid', 'event.title', 'day.day')->willReturn($queryBuilder->reveal());
        $queryBuilder->from('tx_events2_domain_model_day', 'day')->willReturn($queryBuilder->reveal());
        $queryBuilder->expr()->willReturn($expressionBuilder->reveal());
        $queryBuilder->quoteIdentifier('event.uid');
        $queryBuilder
            ->leftJoin(
                'day',
                'tx_events2_domain_model_event',
                'event',
                Argument::cetera()
            )
            ->willReturn($queryBuilder->reveal());

        $queryBuilder->quoteIdentifier('category_mm.uid_foreign');
        $queryBuilder->createNamedParameter('tx_events2_domain_model_event', 2);
        $queryBuilder->createNamedParameter('categories', 2);

        $queryBuilder
            ->leftJoin(
                'event',
                'sys_category_record_mm',
                'category_mm',
                Argument::cetera()
            )
            ->willReturn($queryBuilder->reveal());
        $queryBuilder->createNamedParameter([10, 11, 12], 101);
        $queryBuilder->createNamedParameter([21, 22, 23], 101);
        $queryBuilder->createNamedParameter(Argument::any(), 1);

        $queryBuilder->where(Argument::cetera())->willReturn($queryBuilder->reveal());
        $queryBuilder->expr(Argument::cetera())->willReturn($expressionBuilder->reveal());
        $queryBuilder->execute(Argument::cetera())->willReturn($statement->reveal());

        $connectionPool->getQueryBuilderForTable('tx_events2_domain_model_day')->shouldBeCalled()->willReturn($queryBuilder);

        GeneralUtility::addInstance(ConnectionPool::class, $connectionPool->reveal());

        $this->buildAssertionForDatabaseWithReturnValue(
            'tx_events2_domain_model_holiday',
            [],
            [
                [
                    'expr' => 'eq',
                    'field' => 'month',
                    'value' => (int)$arguments['month']
                ]
            ]
        );

        $json = $this->subject->processAjaxRequest($arguments);
        $result = json_decode($json, true);

        // check if days exists in json
        $this->assertArrayHasKey($day, $result);
        $this->assertArrayHasKey($tomorrow, $result);
        // check if uid 456 and 654 exists in json
        $this->assertSame(456, $result[$day][0]['uid']);
        $this->assertSame(654, $result[$tomorrow][0]['uid']);
        // check if title Test123 and Test321 exists in json
        $this->assertSame('Test123', $result[$day][0]['title']);
        $this->assertSame('Test321', $result[$tomorrow][0]['title']);
        // check if cHashes exists in json
        $this->assertContains('cHash', $result[$day][0]['uri']);
        $this->assertContains('cHash', $result[$tomorrow][0]['uri']);
    }

    /**
     * @test
     */
    public function processAjaxRequestWillGenerateUrisForTwoDayRecords()
    {
        $today = new \DateTime('now midnight');
        $tomorrow = new \DateTime('tomorrow midnight');
        $arguments = [
            'categories' => '10,11,12',
            'month' => $today->format('n'),
            'year' => $today->format('Y'),
            'pidOfListPage' => 4321,
            'storagePids' => '21,22,23'
        ];

        $days = [
            [
                'uid' => 456,
                'title' => 'Test123',
                'day' => $today->format('U')
            ],
            [
                'uid' => 654,
                'title' => 'Test321',
                'day' => $tomorrow->format('U')
            ]
        ];

        /** @var FrontendUserAuthentication|ObjectProphecy $frontendUserAuthentication */
        $frontendUserAuthentication = $this->prophesize(FrontendUserAuthentication::class);
        $frontendUserAuthentication->start()->shouldBeCalled();
        $frontendUserAuthentication->setAndSaveSessionData(Argument::cetera())->shouldBeCalled();
        GeneralUtility::addInstance(FrontendUserAuthentication::class, $frontendUserAuthentication->reveal());

        /** @var Statement|ObjectProphecy $statement */
        $statement = $this->prophesize(Statement::class);
        /** @var ExpressionBuilder|ObjectProphecy $expressionBuilder */
        $expressionBuilder = $this->prophesize(ExpressionBuilder::class);
        /** @var QueryBuilder|ObjectProphecy $queryBuilder */
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        /** @var ConnectionPool|ObjectProphecy $connectionPool */
        $connectionPool = $this->prophesize(ConnectionPool::class);

        $statement->fetchAll()->willReturn($days);

        $expressionBuilder->eq('day.event', Argument::cetera())->shouldBeCalled();
        $expressionBuilder->eq('event.uid', Argument::cetera())->shouldBeCalled();
        $expressionBuilder->eq('category_mm.tablenames', Argument::cetera())->shouldBeCalled();
        $expressionBuilder->eq('category_mm.fieldname', Argument::cetera())->shouldBeCalled();
        $expressionBuilder->andX(Argument::cetera())->shouldBeCalled()->willReturn(new CompositeExpression('AND'));

        $expressionBuilder->in('category_mm.uid_local', Argument::cetera())->shouldBeCalled();
        $expressionBuilder->in('event.pid', Argument::cetera())->shouldBeCalled();
        $expressionBuilder->gte('day.day', Argument::cetera())->shouldBeCalled();
        $expressionBuilder->lt('day.day', Argument::cetera())->shouldBeCalled();

        $queryBuilder->select('event.uid', 'event.title', 'day.day')->willReturn($queryBuilder->reveal());
        $queryBuilder->from('tx_events2_domain_model_day', 'day')->willReturn($queryBuilder->reveal());
        $queryBuilder->expr()->willReturn($expressionBuilder->reveal());
        $queryBuilder->quoteIdentifier('event.uid');
        $queryBuilder
            ->leftJoin(
                'day',
                'tx_events2_domain_model_event',
                'event',
                Argument::cetera()
            )
            ->willReturn($queryBuilder->reveal());

        $queryBuilder->quoteIdentifier('category_mm.uid_foreign');
        $queryBuilder->createNamedParameter('tx_events2_domain_model_event', 2);
        $queryBuilder->createNamedParameter('categories', 2);

        $queryBuilder
            ->leftJoin(
                'event',
                'sys_category_record_mm',
                'category_mm',
                Argument::cetera()
            )
            ->willReturn($queryBuilder->reveal());
        $queryBuilder->createNamedParameter([10, 11, 12], 101);
        $queryBuilder->createNamedParameter([21, 22, 23], 101);
        $queryBuilder->createNamedParameter(Argument::any(), 1);

        $queryBuilder->where(Argument::cetera())->willReturn($queryBuilder->reveal());
        $queryBuilder->expr(Argument::cetera())->willReturn($expressionBuilder->reveal());
        $queryBuilder->execute(Argument::cetera())->willReturn($statement->reveal());

        $connectionPool->getQueryBuilderForTable('tx_events2_domain_model_day')->shouldBeCalled()->willReturn($queryBuilder);

        GeneralUtility::addInstance(ConnectionPool::class, $connectionPool->reveal());

        $this->buildAssertionForDatabaseWithReturnValue(
            'tx_events2_domain_model_holiday',
            [],
            [
                [
                    'expr' => 'eq',
                    'field' => 'month',
                    'value' => (int)$arguments['month']
                ]
            ]
        );

        $json = $this->subject->processAjaxRequest($arguments);
        $result = json_decode($json, true);

        $this->assertRegExp(
            '~^(http|https)://(.*?)id=' . $arguments['pidOfListPage'] . '&tx_events2_events%5Bcontroller%5D=Day&tx_events2_events%5Baction%5D=showByTimestamp&tx_events2_events%5Btimestamp%5D=' . $today->format('U') . '&cHash=[0-9a-f]{32}$~',
            $result[(int)$today->format('d')][0]['uri']
        );
        $this->assertRegExp(
            '~^(http|https)://(.*?)id=' . $arguments['pidOfListPage'] . '&tx_events2_events%5Bcontroller%5D=Day&tx_events2_events%5Baction%5D=showByTimestamp&tx_events2_events%5Btimestamp%5D=' . $tomorrow->format('U') . '&cHash=[0-9a-f]{32}$~',
            $result[(int)$tomorrow->format('d')][0]['uri']
        );
    }

    /**
     * @test
     */
    public function processAjaxRequestWillNotAddCategoryStatements()
    {
        $currentDate = new \DateTime('now');
        $arguments = [
            'month' => $currentDate->format('n'),
            'year' => $currentDate->format('Y'),
            'pidOfListPage' => 4321,
            'storagePids' => '21,22,23'
        ];

        /** @var FrontendUserAuthentication|ObjectProphecy $frontendUserAuthentication */
        $frontendUserAuthentication = $this->prophesize(FrontendUserAuthentication::class);
        $frontendUserAuthentication->start()->shouldBeCalled();
        $frontendUserAuthentication->setAndSaveSessionData(Argument::cetera())->shouldBeCalled();
        GeneralUtility::addInstance(FrontendUserAuthentication::class, $frontendUserAuthentication->reveal());

        /** @var Statement|ObjectProphecy $statement */
        $statement = $this->prophesize(Statement::class);
        /** @var ExpressionBuilder|ObjectProphecy $expressionBuilder */
        $expressionBuilder = $this->prophesize(ExpressionBuilder::class);
        /** @var QueryBuilder|ObjectProphecy $queryBuilder */
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        /** @var ConnectionPool|ObjectProphecy $connectionPool */
        $connectionPool = $this->prophesize(ConnectionPool::class);

        $statement->fetchAll()->willReturn([]);

        $expressionBuilder->eq('day.event', Argument::cetera())->shouldBeCalled();

        $expressionBuilder->in('event.pid', Argument::cetera())->shouldBeCalled();
        $expressionBuilder->gte('day.day', Argument::cetera())->shouldBeCalled();
        $expressionBuilder->lt('day.day', Argument::cetera())->shouldBeCalled();

        $queryBuilder->select('event.uid', 'event.title', 'day.day')->willReturn($queryBuilder->reveal());
        $queryBuilder->from('tx_events2_domain_model_day', 'day')->willReturn($queryBuilder->reveal());
        $queryBuilder->expr()->willReturn($expressionBuilder->reveal());
        $queryBuilder->quoteIdentifier('event.uid');
        $queryBuilder
            ->leftJoin(
                'day',
                'tx_events2_domain_model_event',
                'event',
                Argument::cetera()
            )
            ->willReturn($queryBuilder->reveal());

        $queryBuilder->quoteIdentifier('category_mm.uid_foreign');
        $queryBuilder->createNamedParameter('tx_events2_domain_model_event', 2);
        $queryBuilder->createNamedParameter('categories', 2);

        $queryBuilder->createNamedParameter([21, 22, 23], 101);
        $queryBuilder->createNamedParameter(Argument::any(), 1);

        $queryBuilder->where(Argument::cetera())->willReturn($queryBuilder->reveal());
        $queryBuilder->expr(Argument::cetera())->willReturn($expressionBuilder->reveal());
        $queryBuilder->execute(Argument::cetera())->willReturn($statement->reveal());

        $connectionPool->getQueryBuilderForTable('tx_events2_domain_model_day')->shouldBeCalled()->willReturn($queryBuilder);

        GeneralUtility::addInstance(ConnectionPool::class, $connectionPool->reveal());

        $this->buildAssertionForDatabaseWithReturnValue(
            'tx_events2_domain_model_holiday',
            [],
            [
                [
                    'expr' => 'eq',
                    'field' => 'month',
                    'value' => (int)$arguments['month']
                ]
            ]
        );

        $this->subject->processAjaxRequest($arguments);
    }

    /**
     * @test
     */
    public function processAjaxRequestSetsFirstDayOfMonthToEarliestAllowedDate()
    {
        $this->extConfProphecy->getRecurringPast()->willReturn(0);
        $today = new \DateTime('now midnight');
        $arguments = [
            'month' => $today->format('n'),
            'year' => $today->format('Y'),
            'pidOfListPage' => 4321,
            'storagePids' => '21,22,23'
        ];

        /** @var FrontendUserAuthentication|ObjectProphecy $frontendUserAuthentication */
        $frontendUserAuthentication = $this->prophesize(FrontendUserAuthentication::class);
        $frontendUserAuthentication->start()->shouldBeCalled();
        $frontendUserAuthentication->setAndSaveSessionData(Argument::cetera())->shouldBeCalled();
        GeneralUtility::addInstance(FrontendUserAuthentication::class, $frontendUserAuthentication->reveal());

        /** @var Statement|ObjectProphecy $statement */
        $statement = $this->prophesize(Statement::class);
        /** @var ExpressionBuilder|ObjectProphecy $expressionBuilder */
        $expressionBuilder = $this->prophesize(ExpressionBuilder::class);
        /** @var QueryBuilder|ObjectProphecy $queryBuilder */
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        /** @var ConnectionPool|ObjectProphecy $connectionPool */
        $connectionPool = $this->prophesize(ConnectionPool::class);

        $statement->fetchAll()->willReturn([]);

        $expressionBuilder->eq('day.event', Argument::cetera())->shouldBeCalled();

        $expressionBuilder->in('event.pid', Argument::cetera())->shouldBeCalled();
        $expressionBuilder->gte('day.day', Argument::cetera())->shouldBeCalled();
        $expressionBuilder->lt('day.day', Argument::cetera())->shouldBeCalled();

        $queryBuilder->select('event.uid', 'event.title', 'day.day')->willReturn($queryBuilder->reveal());
        $queryBuilder->from('tx_events2_domain_model_day', 'day')->willReturn($queryBuilder->reveal());
        $queryBuilder->expr()->willReturn($expressionBuilder->reveal());
        $queryBuilder->quoteIdentifier('event.uid');
        $queryBuilder
            ->leftJoin(
                'day',
                'tx_events2_domain_model_event',
                'event',
                Argument::cetera()
            )
            ->willReturn($queryBuilder->reveal());

        $queryBuilder->quoteIdentifier('category_mm.uid_foreign');
        $queryBuilder->createNamedParameter('tx_events2_domain_model_event', 2);
        $queryBuilder->createNamedParameter('categories', 2);

        $queryBuilder->createNamedParameter([21, 22, 23], 101);
        $queryBuilder->createNamedParameter(Argument::any(), 1);

        $queryBuilder->where(Argument::cetera())->willReturn($queryBuilder->reveal());
        $queryBuilder->expr(Argument::cetera())->willReturn($expressionBuilder->reveal());
        $queryBuilder->execute(Argument::cetera())->willReturn($statement->reveal());

        $connectionPool->getQueryBuilderForTable('tx_events2_domain_model_day')->shouldBeCalled()->willReturn($queryBuilder);

        GeneralUtility::addInstance(ConnectionPool::class, $connectionPool->reveal());
        $this->buildAssertionForDatabaseWithReturnValue(
            'tx_events2_domain_model_holiday',
            [],
            [
                [
                    'expr' => 'eq',
                    'field' => 'month',
                    'value' => (int)$arguments['month']
                ]
            ]
        );

        $this->subject->processAjaxRequest($arguments);
    }
}
