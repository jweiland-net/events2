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
use JWeiland\Events2\Utility\DateTimeUtility;
use Prophecy\Argument;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Database\PreparedStatement;
use TYPO3\CMS\Core\Tests\UnitTestCase;
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
     * @var DatabaseConnection
     */
    protected $dbProphecy;

    /**
     * set up.
     */
    public function setUp()
    {
        $this->subject = new FindDaysForMonth\Ajax();
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
            'storagePids' => '543,-3245, , 0, Hello,123Test',
        );
        $expectedArguments = array(
            'categories' => '123,-321,0',
            'month' => 231,
            'year' => 5431,
            'pidOfListPage' => 4321,
            'storagePids' => '543,-3245,0',
        );
        $this->subject->setArguments($arguments);
        $this->assertSame(
            $expectedArguments,
            $this->subject->getArguments()
        );
    }

    /**
     * This test also tests protected function getArgument.
     *
     * @test
     */
    public function processAjaxRequestWithArgumentsAndZeroDaysResultsInEmptyJson()
    {
        $arguments = array(
            'month' => '123',
            'year' => '1234',
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
     * This test also tests protected function getArgument.
     *
     * @test
     */
    public function processAjaxRequestWithArgumentsAndTwoDaysResultsInJson()
    {
        $arguments = array(
            'month' => '123',
            'year' => '1234',
        );
        $days = array(
            array(
                'day' => 1234567890, // 14th
                'uid' => 123,
                'eventUid' => 456,
                'eventTitle' => 'Test123',
            ),
            array(
                'day' => 1234890123, // 17th
                'uid' => 321,
                'eventUid' => 654,
                'eventTitle' => 'Test321',
            ),
        );
        /** @var \JWeiland\Events2\Ajax\FindDaysForMonth\Ajax|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMock('JWeiland\\Events2\\Ajax\\FindDaysForMonth\\Ajax', array('initialize', 'saveMonthAndYearInSession', 'findAllDaysInMonth'));
        $subject->setArguments($arguments);
        $subject->expects($this->once())->method('initialize')->with($this->equalTo($arguments));
        $subject->expects($this->once())->method('saveMonthAndYearInSession')->with($this->equalTo(123), $this->equalTo(1234));
        $subject->expects($this->once())->method('findAllDaysInMonth')->with($this->equalTo(123), $this->equalTo(1234))->willReturn($days);
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
        $day = 456;
        $pageUid = 123;
        $this->subject->setArguments(array('pidOfListPage' => $pageUid));
        $this->subject->injectCacheHashCalculator(new CacheHashCalculator());
        $this->assertRegExp(
            '~^(http|https)://(.*?)id='.$pageUid.'&tx_events2_events%5Bcontroller%5D=Day&tx_events2_events%5Baction%5D=show&tx_events2_events%5Bday%5D='.$day.'&cHash=[0-9a-f]{32}$~',
            $this->subject->getUriForDay($day)
        );
    }

    /**
     * @test
     */
    public function findAllDaysInMonthCallsStatementWithoutCategories()
    {
        $GLOBALS['TCA']['tx_events2_domain_model_event']['ctrl']['enablecolumns']['disabled'] = 'hidden';
        $GLOBALS['TCA']['tx_events2_domain_model_event']['ctrl']['delete'] = 'deleted';

        $rows = array(
            array('Test123'),
            array('Test321'),
        );
        /* @var \TYPO3\CMS\Core\Database\PreparedStatement|\PHPUnit_Framework_MockObject_MockObject $preparedStatement */
        $preparedStatement = $this->getMock('TYPO3\\CMS\\Core\\Database\\PreparedStatement', array('execute', 'fetchAll', 'free'), array(), '', false);
        $preparedStatement->expects($this->once())->method('execute')->with($this->logicalAnd(
            $this->arrayHasKey(':monthBegin'),
            $this->arrayHasKey(':monthEnd'),
            $this->arrayHasKey(':storagePids'),
            $this->logicalNot(
                $this->arrayHasKey(':tablename')
            )
        ));
        $preparedStatement->expects($this->once())->method('fetchAll')->with($this->equalTo(PreparedStatement::FETCH_ASSOC))->will($this->returnValue($rows));
        $preparedStatement->expects($this->once())->method('free');

        /** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $databaseConnection */
        $databaseConnection = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection');
        $databaseConnection->expects($this->once())->method('prepare_SELECTquery')->with(
            $this->logicalAnd(
                $this->stringContains('day.uid'),
                $this->stringContains('day.day'),
                $this->stringContains('eventUid'),
                $this->stringContains('eventTitle')
            ),
            $this->logicalAnd(
                $this->stringContains('LEFT JOIN tx_events2_event_day_mm'),
                $this->stringContains('LEFT JOIN tx_events2_domain_model_event'),
                $this->logicalNot(
                    $this->stringContains('category')
                )
            ),
            $this->logicalAnd(
                $this->stringContains('tx_events2_domain_model_event.hidden=0'),
                $this->stringContains('tx_events2_domain_model_event.deleted=0')
            )
        )->will($this->returnValue($preparedStatement));
        $GLOBALS['TYPO3_DB'] = $databaseConnection;

        /** @var \JWeiland\Events2\Ajax\FindDaysForMonth\Ajax|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMock('JWeiland\\Events2\\Ajax\\FindDaysForMonth\\Ajax', array('getArgument'));
        $subject->expects($this->at(0))->method('getArgument')->with($this->equalTo('categories'))->will($this->returnValue(''));
        $subject->expects($this->at(1))->method('getArgument')->with($this->equalTo('storagePids'))->will($this->returnValue('321,654'));
        $subject->injectDateTimeUtility(new DateTimeUtility());

        $this->assertSame(
            $rows,
            $subject->findAllDaysInMonth(8, 2014)
        );
    }

    /**
     * @test
     */
    public function findAllDaysInMonthCallsStatementWithCategories()
    {
        $GLOBALS['TCA']['tx_events2_domain_model_event']['ctrl']['enablecolumns']['disabled'] = 'hidden';
        $GLOBALS['TCA']['tx_events2_domain_model_event']['ctrl']['delete'] = 'deleted';

        $rows = array(
            array('Test123'),
            array('Test321'),
        );
        /* @var \TYPO3\CMS\Core\Database\PreparedStatement|\PHPUnit_Framework_MockObject_MockObject $databaseConnection */
        $preparedStatement = $this->getMock('TYPO3\\CMS\\Core\\Database\\PreparedStatement', array('execute', 'fetchAll', 'free'), array(), '', false);
        $preparedStatement->expects($this->once())->method('execute')->with($this->logicalAnd(
            $this->arrayHasKey(':monthBegin'),
            $this->arrayHasKey(':monthEnd'),
            $this->arrayHasKey(':storagePids'),
            $this->arrayHasKey(':tablename')
        ));
        $preparedStatement->expects($this->once())->method('fetchAll')->with($this->equalTo(PreparedStatement::FETCH_ASSOC))->will($this->returnValue($rows));
        $preparedStatement->expects($this->once())->method('free');
        /** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $databaseConnection */
        $databaseConnection = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection');
        $databaseConnection->expects($this->once())->method('prepare_SELECTquery')->with(
            $this->logicalAnd(
                $this->stringContains('day.uid'),
                $this->stringContains('day.day'),
                $this->stringContains('eventUid'),
                $this->stringContains('eventTitle')
            ),
            $this->logicalAnd(
                $this->stringContains('LEFT JOIN tx_events2_event_day_mm'),
                $this->stringContains('LEFT JOIN tx_events2_domain_model_event'),
                $this->stringContains('LEFT JOIN sys_category_record_mm')
            ),
            $this->logicalAnd(
                $this->stringContains('sys_category_record_mm.uid_local IN (123,456)'),
                $this->stringContains('tx_events2_domain_model_event.hidden=0'),
                $this->stringContains('tx_events2_domain_model_event.deleted=0')
            )
        )->will($this->returnValue($preparedStatement));
        $GLOBALS['TYPO3_DB'] = $databaseConnection;

        /** @var \JWeiland\Events2\Ajax\FindDaysForMonth\Ajax|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMock('JWeiland\\Events2\\Ajax\\FindDaysForMonth\\Ajax', array('getArgument'));
        $subject->expects($this->at(0))->method('getArgument')->with($this->equalTo('categories'))->will($this->returnValue('123,456'));
        $subject->expects($this->at(1))->method('getArgument')->with($this->equalTo('storagePids'))->will($this->returnValue('321,654'));
        $subject->injectDateTimeUtility(new DateTimeUtility());

        $this->assertSame(
            $rows,
            $subject->findAllDaysInMonth(8, 2014)
        );
    }
}
