<?php

namespace JWeiland\Events2\Tests\Unit\Ajax;

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

use JWeiland\Events2\Ajax\FindDaysForMonth;
use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Service\DatabaseService;
use JWeiland\Events2\Tests\Unit\AbstractUnitTestCase;
use JWeiland\Events2\Tests\Unit\Utility\AccessibleProxies\ExtensionManagementUtilityAccessibleProxy;
use JWeiland\Events2\Utility\DateTimeUtility;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Category\CategoryRegistry;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;

/**
 * Test case.
 */
class FindDaysForMonthTest extends AbstractUnitTestCase
{
    /**
     * @var FindDaysForMonth
     */
    protected $subject;

    /**
     * @var ExtConf|ObjectProphecy
     */
    protected $extConfProphecy;

    /**
     * @var FrontendUserAuthentication|ObjectProphecy
     */
    protected $frontendUserAuthenticationProphecy;

    /**
     * @var PhpFrontend|ObjectProphecy
     */
    protected $phpFrontendProphecy;

    /**
     * @var CacheManager|ObjectProphecy
     */
    protected $cacheManagerProphecy;

    /**
     * set up.
     */
    public function setUp()
    {
        $this->extConfProphecy = $this->prophesize(ExtConf::class);
        $this->extConfProphecy->getRecurringPast()->willReturn(3);
        $this->extConfProphecy->getRecurringFuture()->willReturn(6);

        $this->frontendUserAuthenticationProphecy = $this->prophesize(FrontendUserAuthentication::class);

        $cacheData = [
            'categoryRegistry' => serialize(new CategoryRegistry())
        ];

        $this->phpFrontendProphecy = $this->prophesize(PhpFrontend::class);
        $this->phpFrontendProphecy->require(Argument::any())->shouldBeCalled()->willReturn($cacheData);

        /** @var CacheManager|ObjectProphecy $cacheManagerProphecy */
        $this->cacheManagerProphecy = $this->prophesize(CacheManager::class);
        $this->cacheManagerProphecy
            ->getCache('cache_core')
            ->shouldBeCalled()
            ->willReturn($this->phpFrontendProphecy->reveal());
        GeneralUtility::setSingletonInstance(CacheManager::class, $this->cacheManagerProphecy->reveal());

        $this->subject = new FindDaysForMonth(
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
        parent::tearDown();
        ExtensionManagementUtilityAccessibleProxy::setCacheManager(null);
        unset($this->subject);
    }

    /**
     * DataProvider for arguments
     *
     * @return array
     */
    public function dataProviderForProcessAjaxRequestForcesTooHighMonthAndYearInRange()
    {
        $arguments = [];
        $arguments['negative values'] = ['http://www.example.com/?tx_events2_events[arguments][month]=-50&tx_events2_events[arguments][year]=-2400', ['month' => '01', 'year' => '1500'], 1];
        $arguments['valid int values'] = ['http://www.example.com/?tx_events2_events[arguments][month]=10&tx_events2_events[arguments][year]=2100', ['month' => '10', 'year' => '2100'], 10];
        $arguments['too huge values'] = ['http://www.example.com/?tx_events2_events[arguments][month]=250&tx_events2_events[arguments][year]=12050', ['month' => '12', 'year' => '2500'], 12];
        $arguments['add 0 to small numbers'] = ['http://www.example.com/?tx_events2_events[arguments][month]=3&tx_events2_events[arguments][year]=2145', ['month' => '03', 'year' => '2145'], 3];

        return $arguments;
    }

    /**
     * @test
     * @dataProvider dataProviderForProcessAjaxRequestForcesTooHighMonthAndYearInRange
     */
    public function processAjaxRequestForcesTooHighMonthAndYearInRange($uri, $expectedArguments, $expectedMonth)
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

        $this->subject->processRequest(new ServerRequest($uri));
    }

    /**
     * @test
     */
    public function processAjaxRequestWillFindDaysInRangeOfFirstAndLastDateOfMonth()
    {
        $currentDate = new \DateTime('now midnight');
        $uri = sprintf(
            'http://www.example.com/?tx_events2_events[arguments][pidOfListPage]=%d&tx_events2_events[arguments][month]=%d&tx_events2_events[arguments][year]=%d',
            4321,
            (int)$currentDate->format('n'),
            (int)$currentDate->format('Y')
        );
        $startDate = clone $currentDate;
        $startDate->modify('first day of this month');
        $endDate = clone $currentDate;
        $endDate->modify('last day of this month')->modify('tomorrow');

        /** @var FrontendUserAuthentication|ObjectProphecy $frontendUserAuthentication */
        $frontendUserAuthentication = $this->prophesize(FrontendUserAuthentication::class);
        $frontendUserAuthentication->start()->shouldBeCalled();
        $frontendUserAuthentication->setAndSaveSessionData(Argument::cetera())->shouldBeCalled();
        GeneralUtility::addInstance(FrontendUserAuthentication::class, $frontendUserAuthentication->reveal());

        /** @var DatabaseService $databaseServiceProphecy */
        $databaseServiceProphecy = $this->prophesize(DatabaseService::class);
        $databaseServiceProphecy->getDaysInRange($startDate, $endDate, [], [])->shouldBeCalled()->willReturn([]);
        GeneralUtility::addInstance(DatabaseService::class, $databaseServiceProphecy->reveal());

        $this->buildAssertionForDatabaseWithReturnValue(
            'tx_events2_domain_model_holiday',
            [],
            [
                [
                    'expr' => 'eq',
                    'field' => 'month',
                    'value' => (int)$currentDate->format('n')
                ]
            ]
        );

        $this->subject->processRequest(new ServerRequest($uri));
    }

    /**
     * @test
     */
    public function processAjaxRequestWillFindDaysInRangeOfFirstAndLastDateOfMonthWithStoragePids()
    {
        $currentDate = new \DateTime('now midnight');
        $uri = sprintf(
            'http://www.example.com/?tx_events2_events[arguments][storagePids]=%s&tx_events2_events[arguments][pidOfListPage]=%d&tx_events2_events[arguments][month]=%d&tx_events2_events[arguments][year]=%d',
            '21,22,23',
            4321,
            (int)$currentDate->format('n'),
            (int)$currentDate->format('Y')
        );
        $startDate = clone $currentDate;
        $startDate->modify('first day of this month');
        $endDate = clone $currentDate;
        $endDate->modify('last day of this month')->modify('tomorrow');

        /** @var FrontendUserAuthentication|ObjectProphecy $frontendUserAuthentication */
        $frontendUserAuthentication = $this->prophesize(FrontendUserAuthentication::class);
        $frontendUserAuthentication->start()->shouldBeCalled();
        $frontendUserAuthentication->setAndSaveSessionData(Argument::cetera())->shouldBeCalled();
        GeneralUtility::addInstance(FrontendUserAuthentication::class, $frontendUserAuthentication->reveal());

        /** @var DatabaseService $databaseServiceProphecy */
        $databaseServiceProphecy = $this->prophesize(DatabaseService::class);
        $databaseServiceProphecy->getDaysInRange($startDate, $endDate, [21, 22, 23], [])->shouldBeCalled()->willReturn([]);
        GeneralUtility::addInstance(DatabaseService::class, $databaseServiceProphecy->reveal());

        $this->buildAssertionForDatabaseWithReturnValue(
            'tx_events2_domain_model_holiday',
            [],
            [
                [
                    'expr' => 'eq',
                    'field' => 'month',
                    'value' => (int)$currentDate->format('n')
                ]
            ]
        );

        $this->subject->processRequest(new ServerRequest($uri));
    }

    /**
     * @test
     */
    public function processAjaxRequestWillFindDaysInRangeOfFirstAndLastDateOfMonthWithStoragePidsAndCategories()
    {
        $currentDate = new \DateTime('now midnight');
        $uri = sprintf(
            'http://www.example.com/?tx_events2_events[arguments][categories]=%s&tx_events2_events[arguments][storagePids]=%s&tx_events2_events[arguments][pidOfListPage]=%d&tx_events2_events[arguments][month]=%d&tx_events2_events[arguments][year]=%d',
            '31,32,33',
            '21,22,23',
            4321,
            (int)$currentDate->format('n'),
            (int)$currentDate->format('Y')
        );
        $startDate = clone $currentDate;
        $startDate->modify('first day of this month');
        $endDate = clone $currentDate;
        $endDate->modify('last day of this month')->modify('tomorrow');

        /** @var FrontendUserAuthentication|ObjectProphecy $frontendUserAuthentication */
        $frontendUserAuthentication = $this->prophesize(FrontendUserAuthentication::class);
        $frontendUserAuthentication->start()->shouldBeCalled();
        $frontendUserAuthentication->setAndSaveSessionData(Argument::cetera())->shouldBeCalled();
        GeneralUtility::addInstance(FrontendUserAuthentication::class, $frontendUserAuthentication->reveal());

        /** @var DatabaseService $databaseServiceProphecy */
        $databaseServiceProphecy = $this->prophesize(DatabaseService::class);
        $databaseServiceProphecy->getDaysInRange($startDate, $endDate, [21, 22, 23], [31, 32, 33])->shouldBeCalled()->willReturn([]);
        GeneralUtility::addInstance(DatabaseService::class, $databaseServiceProphecy->reveal());

        $this->buildAssertionForDatabaseWithReturnValue(
            'tx_events2_domain_model_holiday',
            [],
            [
                [
                    'expr' => 'eq',
                    'field' => 'month',
                    'value' => (int)$currentDate->format('n')
                ]
            ]
        );

        $this->subject->processRequest(new ServerRequest($uri));
    }

    /**
     * @test
     */
    public function processAjaxRequestWillFindDaysInRangeOfEarliestDateAndLastDateOfMonth()
    {
        $currentDate = new \DateTime('-3 months midnight');
        $uri = sprintf(
            'http://www.example.com/?tx_events2_events[arguments][pidOfListPage]=%d&tx_events2_events[arguments][month]=%d&tx_events2_events[arguments][year]=%d',
            4321,
            (int)$currentDate->format('n'),
            (int)$currentDate->format('Y')
        );
        $startDate = clone $currentDate;
        $endDate = clone $currentDate;
        $endDate->modify('last day of this month')->modify('tomorrow');

        /** @var FrontendUserAuthentication|ObjectProphecy $frontendUserAuthentication */
        $frontendUserAuthentication = $this->prophesize(FrontendUserAuthentication::class);
        $frontendUserAuthentication->start()->shouldBeCalled();
        $frontendUserAuthentication->setAndSaveSessionData(Argument::cetera())->shouldBeCalled();
        GeneralUtility::addInstance(FrontendUserAuthentication::class, $frontendUserAuthentication->reveal());

        /** @var DatabaseService $databaseServiceProphecy */
        $databaseServiceProphecy = $this->prophesize(DatabaseService::class);
        $databaseServiceProphecy->getDaysInRange($startDate, $endDate, [], [])->shouldBeCalled()->willReturn([]);
        GeneralUtility::addInstance(DatabaseService::class, $databaseServiceProphecy->reveal());

        $this->buildAssertionForDatabaseWithReturnValue(
            'tx_events2_domain_model_holiday',
            [],
            [
                [
                    'expr' => 'eq',
                    'field' => 'month',
                    'value' => (int)$currentDate->format('n')
                ]
            ]
        );

        $this->subject->processRequest(new ServerRequest($uri));
    }

    /**
     * @test
     */
    public function processAjaxRequestWillFindDaysInRangeOfFirstDateAndLatestDateOfMonth()
    {
        $currentDate = new \DateTime('+6 months midnight');
        $uri = sprintf(
            'http://www.example.com/?tx_events2_events[arguments][pidOfListPage]=%d&tx_events2_events[arguments][month]=%d&tx_events2_events[arguments][year]=%d',
            4321,
            (int)$currentDate->format('n'),
            (int)$currentDate->format('Y')
        );
        $startDate = clone $currentDate;
        $startDate->modify('first day of this month');
        $endDate = clone $currentDate;
        $endDate->modify('tomorrow');

        /** @var FrontendUserAuthentication|ObjectProphecy $frontendUserAuthentication */
        $frontendUserAuthentication = $this->prophesize(FrontendUserAuthentication::class);
        $frontendUserAuthentication->start()->shouldBeCalled();
        $frontendUserAuthentication->setAndSaveSessionData(Argument::cetera())->shouldBeCalled();
        GeneralUtility::addInstance(FrontendUserAuthentication::class, $frontendUserAuthentication->reveal());

        /** @var DatabaseService $databaseServiceProphecy */
        $databaseServiceProphecy = $this->prophesize(DatabaseService::class);
        $databaseServiceProphecy->getDaysInRange($startDate, $endDate, [], [])->shouldBeCalled()->willReturn([]);
        GeneralUtility::addInstance(DatabaseService::class, $databaseServiceProphecy->reveal());

        $this->buildAssertionForDatabaseWithReturnValue(
            'tx_events2_domain_model_holiday',
            [],
            [
                [
                    'expr' => 'eq',
                    'field' => 'month',
                    'value' => (int)$currentDate->format('n')
                ]
            ]
        );

        $this->subject->processRequest(new ServerRequest($uri));
    }
}
