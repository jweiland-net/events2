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
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Database\PreparedStatement;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
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
     * @var \JWeiland\Events2\Ajax\FindDaysForMonth\Ajax
     */
    protected $subject;
    
    /**
     * @var \JWeiland\Events2\Domain\Repository\DayRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dayRepository;
    
    /**
     * @var Query|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $query;
    
    /**
     * @var DatabaseConnection
     */
    protected $dbProphecy;

    /**
     * set up.
     */
    public function setUp()
    {
        $this->subject = new FindDaysForMonth\Ajax();
        $this->dayRepository = $this->getMock(DayRepository::class, array(), array(), '', false);
        $this->query = $this->getMock(Query::class, array(), array(), '', false);
        $this->dbProphecy = $this->prophesize(DatabaseConnection::class);
        $GLOBALS['TYPO3_DB'] = $this->dbProphecy->reveal();
        $GLOBALS['TYPO3_LOADED_EXT'] = array(
            'events2' => array()
        );
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
     * dataProvider for initializeWithInvalidValuesResultsInException.
     *
     * @return array
     */
    public function dataProviderForInvalidValues()
    {
        $invalidValues = array();
        $invalidValues['string'] = array('Hello');
        $invalidValues['integer'] = array(123);
        $invalidValues['boolean'] = array(true);
        $invalidValues['object'] = array(new \stdClass());

        return $invalidValues;
    }

    /**
     * @test
     *
     * @param mixed $invalidValue
     * @dataProvider dataProviderForInvalidValues
     * @expectedException \PHPUnit_Framework_Error
     */
    public function initializeWithInvalidValuesResultsInException($invalidValue)
    {
        $this->subject->initialize($invalidValue);
    }

    /**
     * @test
     */
    public function setArgumentsSanitizesAndSetsArguments()
    {
        $arguments = array(
            'categories' => '123,-321 , , 0, Hallo,123Test',
            'month' => '231',
            'year' => '5431',
            'pidOfListPage' => '4321',
            'storagePids' => '543,-3245, , 0, Hello,123Test'
        );
        $expectedArguments = array(
            'categories' => '123,-321,0',
            'month' => 231,
            'year' => 5431,
            'pidOfListPage' => 4321,
            'storagePids' => '543,-3245,0'
        );
        $this->subject->setArguments($arguments);
        $this->assertSame(
            $expectedArguments,
            $this->subject->getArguments()
        );
    }
    
    /**
     * @test
     */
    public function processAjaxRequestWithArgumentsAndZeroDaysResultsInEmptyJson()
    {
        $arguments = array(
            'month' => '123',
            'year' => '1234'
        );
        /** @var \JWeiland\Events2\Ajax\FindDaysForMonth\Ajax|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMock('JWeiland\\Events2\\Ajax\\FindDaysForMonth\\Ajax', array('initialize', 'saveMonthAndYearInSession', 'findAllDaysInMonth'));
        $subject->setArguments($arguments);
        $subject->expects($this->once())->method('initialize')->with($this->equalTo($arguments));
        $subject->expects($this->once())->method('saveMonthAndYearInSession')->with($this->equalTo(123), $this->equalTo(1234));
        $subject->expects($this->once())->method('findAllDaysInMonth')->with($this->equalTo(123), $this->equalTo(1234))->will($this->returnValue(array()));
        $this->assertSame(
            '[]',
            $subject->processAjaxRequest($arguments)
        );
    }
    
    /**
     * @test
     */
    public function processAjaxRequestSavesMonthAndYearAsString()
    {
        $arguments = array(
            'month' => 9,
            'year' => 2016
        );
    
        /** @var FrontendUserAuthentication|\PHPUnit_Framework_MockObject_MockObject $frontendUserAuthentication */
        $frontendUserAuthentication = $this->getMock(FrontendUserAuthentication::class);
        $frontendUserAuthentication
            ->expects($this->once())
            ->method('start');
        $frontendUserAuthentication
            ->expects($this->once())
            ->method('setAndSaveSessionData')
            ->with(
                $this->equalTo('events2MonthAndYearForCalendar'),
                $this->equalTo(array(
                    'month' => '09',
                    'year' => '2016'
                ))
            );
        
        /** @var \JWeiland\Events2\Ajax\FindDaysForMonth\Ajax|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMock('JWeiland\\Events2\\Ajax\\FindDaysForMonth\\Ajax', array('initialize', 'getFrontendUserAuthentication', 'findAllDaysInMonth'));
        $subject->setArguments($arguments);
        $subject
            ->expects($this->once())
            ->method('initialize')
            ->with($this->equalTo($arguments));
        $subject
            ->expects($this->once())
            ->method('getFrontendUserAuthentication')
            ->willReturn($frontendUserAuthentication);
        $subject
            ->expects($this->once())
            ->method('findAllDaysInMonth')
            ->with($this->equalTo(9), $this->equalTo(2016))
            ->willReturn(array());
        
        $this->assertSame(
            '[]',
            $subject->processAjaxRequest($arguments)
        );
    }
    
    /**
     * This test also tests protected function getArgument.
     *
     * @test
     */
    public function processAjaxRequestWithArgumentsAndTwoDaysResultsInJson()
    {
        $arguments = array(
            'month' => '9',
            'year' => '2015'
        );
        
        $event1 = new Event();
        $event1->_setProperty('uid', 456);
        $event1->setTitle('Test123');
        $event2 = new Event();
        $event2->_setProperty('uid', 654);
        $event2->setTitle('Test321');
        $day1 = new Day();
        $day1->setDay(new \DateTime('14.09.2015'));
        $day1->setEvent($event1);
        $day2 = new Day();
        $day2->setDay(new \DateTime('17.09.2015'));
        $day2->setEvent($event2);
        $days = array($day1, $day2);
        
        /** @var \JWeiland\Events2\Ajax\FindDaysForMonth\Ajax|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMock('JWeiland\\Events2\\Ajax\\FindDaysForMonth\\Ajax', array('initialize', 'saveMonthAndYearInSession', 'findAllDaysInMonth'));
        $subject->setArguments($arguments);
        $subject->expects($this->once())->method('initialize')->with($this->equalTo($arguments));
        $subject->expects($this->once())->method('saveMonthAndYearInSession')->with($this->equalTo(9), $this->equalTo(2015));
        $subject->expects($this->once())->method('findAllDaysInMonth')->with($this->equalTo(9), $this->equalTo(2015))->willReturn($days);
        $subject->injectDateTimeUtility(new DateTimeUtility());
        $subject->injectCacheHashCalculator(new CacheHashCalculator());

        $json = $subject->processAjaxRequest($arguments);
        $result = json_decode($json, true);

        // check if day 14 and 17 exists in json
        $this->assertArrayHasKey(14, $result);
        $this->assertArrayHasKey(17, $result);
        // check if uid 456 and 654 exists in json
        $this->assertSame(456, $result[14][0]['uid']);
        $this->assertSame(654, $result[17][0]['uid']);
        // check if title Test123 and Test321 exists in json
        $this->assertSame('Test123', $result[14][0]['title']);
        $this->assertSame('Test321', $result[17][0]['title']);
        // check if cHashes exists in json
        $this->assertContains('cHash', $result[14][0]['uri']);
        $this->assertContains('cHash', $result[17][0]['uri']);
    }

    /**
     * @test
     */
    public function getUriForDayWithDayUidAsArgumentResultsInUriWithGeneratedCHash()
    {
        $timestamp = 1234567890;
        $pageUid = 123;
        $this->subject->setArguments(array('pidOfListPage' => $pageUid));
        $this->subject->injectCacheHashCalculator(new CacheHashCalculator());
        $this->assertRegExp(
            '~^(http|https)://(.*?)id='.$pageUid.'&tx_events2_events%5Bcontroller%5D=Day&tx_events2_events%5Baction%5D=showByTimestamp&tx_events2_events%5Btimestamp%5D=' . $timestamp . '&cHash=[0-9a-f]{32}$~',
            $this->subject->getUriForDay($timestamp)
        );
    }

    /**
     * @test
     */
    public function findAllDaysInMonthCallsStatementWithoutCategories()
    {
        $firstDayOfMonth = new \DateTime('01.08.2014 00:00:00');
        $lastDayOfMonth = new \DateTime('01.09.2014 00:00:00');
        $this->query
            ->expects($this->once())
            ->method('getQuerySettings')
            ->willReturn(new Typo3QuerySettings());
        $this->query
            ->expects($this->never())
            ->method('contains');
        $this->query
            ->expects($this->never())
            ->method('logicalOr');
        $this->query
            ->expects($this->once())
            ->method('greaterThanOrEqual')
            ->with(
                $this->equalTo('day'),
                $this->equalTo($firstDayOfMonth)
            );
        $this->query
            ->expects($this->once())
            ->method('lessThan')
            ->with(
                $this->equalTo('day'),
                $this->equalTo($lastDayOfMonth)
            );
        $this->query
            ->expects($this->once())
            ->method('logicalAnd');
        $this->query
            ->expects($this->once())
            ->method('matching')
            ->willReturn($this->query);

        $this->dayRepository
            ->expects($this->once())
            ->method('createQuery')
            ->willReturn($this->query);
        
        $this->subject->injectDateTimeUtility(new DateTimeUtility());
        $this->subject->injectDayRepository($this->dayRepository);
        
        $this->subject->findAllDaysInMonth(8, 2014);
    }

    /**
     * @test
     */
    public function findAllDaysInMonthCallsStatementWithCategories()
    {
        $firstDayOfMonth = new \DateTime('01.08.2014 00:00:00');
        $lastDayOfMonth = new \DateTime('01.09.2014 00:00:00');
        $this->query
            ->expects($this->once())
            ->method('getQuerySettings')
            ->willReturn(new Typo3QuerySettings());
        $this->query
            ->expects($this->exactly(2))
            ->method('contains');
        $this->query
            ->expects($this->once())
            ->method('logicalOr');
        $this->query
            ->expects($this->once())
            ->method('greaterThanOrEqual')
            ->with(
                $this->equalTo('day'),
                $this->equalTo($firstDayOfMonth)
            );
        $this->query
            ->expects($this->once())
            ->method('lessThan')
            ->with(
                $this->equalTo('day'),
                $this->equalTo($lastDayOfMonth)
            );
        $this->query
            ->expects($this->once())
            ->method('logicalAnd');
        $this->query
            ->expects($this->once())
            ->method('matching')
            ->willReturn($this->query);
    
        $this->dayRepository
            ->expects($this->once())
            ->method('createQuery')
            ->willReturn($this->query);
    
        $this->subject->injectDateTimeUtility(new DateTimeUtility());
        $this->subject->injectDayRepository($this->dayRepository);
        $this->subject->setArguments(array(
            'categories' => '123,321'
        ));
    
        $this->subject->findAllDaysInMonth(8, 2014);
    }
}
