<?php

namespace JWeiland\Events2\Tests\Unit\Ajax\FindDaysForMonth;

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
use JWeiland\Events2\Ajax\FindDaysForMonth;
use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Utility\DateTimeUtility;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Tests\AccessibleObjectInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;

/**
 * Test case.
 *
 * @author Stefan Froemken <projects@jweiland.net>
 */
class AjaxTest extends UnitTestCase
{
    /**
     * @var FindDaysForMonth\Ajax|\PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface
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
     * @var Query|ObjectProphecy
     */
    protected $queryProphecy;

    /**
     * @var DatabaseConnection
     */
    protected $dbProphecy;

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
        $this->dayRepositoryProphecy = $this->prophesize(DayRepository::class);
        $this->querySettingsProphecy = $this->prophesize(QuerySettingsInterface::class);
        $this->queryProphecy = $this->prophesize(Query::class);

        $this->dbProphecy = $this->prophesize(DatabaseConnection::class);
        $GLOBALS['TYPO3_DB'] = $this->dbProphecy->reveal();

        $boostrap = $this
            ->getMockBuilder(Bootstrap::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->subject = $this->getAccessibleMock(FindDaysForMonth\Ajax::class, ['getFrontendUserAuthentication', 'getBootstrap']);
        $this->subject->expects($this->once())->method('getBootstrap')->willReturn($boostrap);
        $this->subject->_set('extConf', $this->extConfProphecy->reveal());
        $this->subject->_set('dateTimeUtility', new DateTimeUtility());
        $this->subject->_set('dayRepository', $this->dayRepositoryProphecy->reveal());
        $this->subject->_set('cacheHashCalculator', new CacheHashCalculator());
        $this->subject
            ->expects($this->any())
            ->method('getFrontendUserAuthentication')
            ->willReturn($this->frontendUserAuthenticationProphecy->reveal());
    }

    /**
     * tear down.
     */
    public function tearDown()
    {
        unset($this->subject);
        unset($this->dayRepository);
        unset($this->query);
        unset($this->dbProphecy);
    }

    /**
     * @test
     */
    public function processAjaxRequestSanitizesArguments()
    {
        $arguments = [
            'categories' => '123,-321 , , 0, Hallo,123Test',
            'month' => '11',
            'year' => '2024',
            'pidOfListPage' => '4321',
            'storagePids' => '543,-3245, ,  123Test'
        ];
        $expectedArguments = [
            'categories' => '123,-321,0',
            'month' => 11,
            'year' => 2024,
            'pidOfListPage' => 4321,
            'storagePids' => '543,-3245'
        ];
        $this->subject->processAjaxRequest($arguments);
        $this->assertSame($expectedArguments, $this->subject->_get('arguments'));
    }

    /**
     * @test
     */
    public function processAjaxRequestForcesTooHighMonthAndYearInRange()
    {
        $arguments = [
            'categories' => '10,11,12',
            'month' => '243',
            'year' => '23412',
            'pidOfListPage' => 4321,
            'storagePids' => '21,22,23'
        ];
        $expectedArguments = [
            'categories' => '10,11,12',
            'month' => 12,
            'year' => 2500,
            'pidOfListPage' => 4321,
            'storagePids' => '21,22,23'
        ];
        $this->subject->processAjaxRequest($arguments);
        $this->assertSame($expectedArguments, $this->subject->_get('arguments'));
    }

    /**
     * @test
     */
    public function processAjaxRequestForcesTooLowMonthAndYearInRange()
    {
        $arguments = [
            'categories' => '10,11,12',
            'month' => '-12',
            'year' => '324',
            'pidOfListPage' => 4321,
            'storagePids' => '21,22,23'
        ];
        $expectedArguments = [
            'categories' => '10,11,12',
            'month' => 1,
            'year' => 1500,
            'pidOfListPage' => 4321,
            'storagePids' => '21,22,23'
        ];
        $this->subject->processAjaxRequest($arguments);
        $this->assertSame($expectedArguments, $this->subject->_get('arguments'));
    }

    /**
     * @test
     */
    public function processAjaxRequestPrependsZerosToMonthValuesAndCastsThemToString()
    {
        $arguments = [
            'categories' => '10,11,12',
            'month' => '7',
            'year' => '2499',
            'pidOfListPage' => 4321,
            'storagePids' => '21,22,23'
        ];
        $this->frontendUserAuthenticationProphecy->start()->shouldBeCalled();
        $this->frontendUserAuthenticationProphecy->setAndSaveSessionData(
            Argument::exact('events2MonthAndYearForCalendar'),
            Argument::exact([
                'month' => '07',
                'year' => '2499'
            ])
        )->shouldBeCalled();

        $this->subject->processAjaxRequest($arguments);
    }

    /**
     * @test
     */
    public function processAjaxRequestWillGenerateTwoDayRecordsAsJson()
    {
        $currentDate = new \DateTime('now');
        $day = (int)$currentDate->format('d');
        $tomorrow = $day + 1;
        $arguments = [
            'categories' => '10,11,12',
            'month' => $currentDate->format('n'),
            'year' => $currentDate->format('Y'),
            'pidOfListPage' => 4321,
            'storagePids' => '21,22,23'
        ];

        $event1 = new Event();
        $event1->_setProperty('uid', 456);
        $event1->setTitle('Test123');
        $event2 = new Event();
        $event2->_setProperty('uid', 654);
        $event2->setTitle('Test321');
        $day1 = new Day();
        $day1->setDay(new \DateTime('now'));
        $day1->setEvent($event1);
        $day2 = new Day();
        $day2->setDay(new \DateTime('tomorrow'));
        $day2->setEvent($event2);
        $days = [$day1, $day2];

        $this->queryProphecy->getQuerySettings()->willReturn($this->querySettingsProphecy->reveal());
        $this->queryProphecy->getQuerySettings()->shouldBeCalled();
        $this->queryProphecy->contains(Argument::exact('event.categories'), Argument::type('integer'))->shouldBeCalledTimes(3);
        $this->queryProphecy->logicalOr(Argument::any())->shouldBeCalled();
        $this->queryProphecy->greaterThanOrEqual(Argument::exact('day'), Argument::any())->shouldBeCalled();
        $this->queryProphecy->lessThan(Argument::exact('day'), Argument::any())->shouldBeCalled();
        $this->queryProphecy->logicalAnd(Argument::any())->shouldBeCalled();
        $this->queryProphecy->matching(Argument::any())->willReturn($this->queryProphecy->reveal());
        $this->queryProphecy->execute()->willReturn($days);

        $this->dayRepositoryProphecy->createQuery()->willReturn($this->queryProphecy);

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

        $event1 = new Event();
        $event1->_setProperty('uid', 456);
        $event1->setTitle('Test123');
        $event2 = new Event();
        $event2->_setProperty('uid', 654);
        $event2->setTitle('Test321');
        $day1 = new Day();
        $day1->setDay($today);
        $day1->setEvent($event1);
        $day2 = new Day();
        $day2->setDay($tomorrow);
        $day2->setEvent($event2);
        $days = [$day1, $day2];

        $this->queryProphecy->getQuerySettings()->willReturn($this->querySettingsProphecy->reveal());
        $this->queryProphecy->getQuerySettings()->shouldBeCalled();
        $this->queryProphecy->contains(Argument::exact('event.categories'), Argument::type('integer'))->shouldBeCalledTimes(3);
        $this->queryProphecy->logicalOr(Argument::any())->shouldBeCalled();
        $this->queryProphecy->greaterThanOrEqual(Argument::exact('day'), Argument::any())->shouldBeCalled();
        $this->queryProphecy->lessThan(Argument::exact('day'), Argument::any())->shouldBeCalled();
        $this->queryProphecy->logicalAnd(Argument::any())->shouldBeCalled();
        $this->queryProphecy->matching(Argument::any())->willReturn($this->queryProphecy->reveal());
        $this->queryProphecy->execute()->willReturn($days);

        $this->dayRepositoryProphecy->createQuery()->willReturn($this->queryProphecy);

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

        $this->queryProphecy->getQuerySettings()->willReturn($this->querySettingsProphecy->reveal());
        $this->queryProphecy->getQuerySettings()->shouldBeCalled();
        $this->queryProphecy->contains()->shouldNotBeCalled();
        $this->queryProphecy->logicalOr()->shouldNotBeCalled();
        $this->queryProphecy->greaterThanOrEqual(Argument::exact('day'), Argument::any())->shouldBeCalled();
        $this->queryProphecy->lessThan(Argument::exact('day'), Argument::any())->shouldBeCalled();
        $this->queryProphecy->logicalAnd(Argument::any())->shouldBeCalled();
        $this->queryProphecy->matching(Argument::any())->willReturn($this->queryProphecy->reveal());
        $this->queryProphecy->execute()->willReturn([]);
        $this->dayRepositoryProphecy->createQuery()->willReturn($this->queryProphecy);

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

        $this->queryProphecy->getQuerySettings()->willReturn($this->querySettingsProphecy->reveal());
        $this->queryProphecy->getQuerySettings()->shouldBeCalled();
        $this->queryProphecy->contains()->shouldNotBeCalled();
        $this->queryProphecy->logicalOr()->shouldNotBeCalled();
        $this->queryProphecy->greaterThanOrEqual(Argument::exact('day'), Argument::exact($today))->shouldBeCalled();
        $this->queryProphecy->lessThan(Argument::exact('day'), Argument::any())->shouldBeCalled();
        $this->queryProphecy->logicalAnd(Argument::any())->shouldBeCalled();
        $this->queryProphecy->matching(Argument::any())->willReturn($this->queryProphecy->reveal());
        $this->queryProphecy->execute()->willReturn([]);
        $this->dayRepositoryProphecy->createQuery()->willReturn($this->queryProphecy);

        $this->subject->processAjaxRequest($arguments);
    }

    /**
     * @test
     */
    public function processAjaxRequestWillReturnEmptyArrayIfMonthAndYearIsOutOfRange()
    {
        $this->extConfProphecy->getRecurringPast()->willReturn(2);
        $threeMonthAgo = new \DateTime('now midnight');
        $threeMonthAgo->modify('-3 months');

        $arguments = [
            'month' => $threeMonthAgo->format('n'),
            'year' => $threeMonthAgo->format('Y'),
            'pidOfListPage' => 4321,
            'storagePids' => '21,22,23'
        ];

        $this->assertSame(
            '[]',
            $this->subject->processAjaxRequest($arguments)
        );
    }
}
