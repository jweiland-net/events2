<?php

namespace JWeiland\Events2\Tests\Unit\Service;

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
use JWeiland\Events2\Service\DayRelations;
use TYPO3\CMS\Core\Tests\UnitTestcase;

/**
 * Test case for class \JWeiland\Events2\Service\DayRelations.
 *
 *
 * @author Stefan Froemken <projects@jweiland.net>
 */
class DayRelationsTest extends UnitTestcase
{
    /**
     * @var \JWeiland\Events2\Service\DayRelations
     */
    protected $subject;

    /**
     * set up.
     */
    public function setUp()
    {
        $this->subject = new DayRelations();
    }

    /**
     * tear down.
     */
    public function tearDown()
    {
        unset($this->subject);
    }

    /**
     * dataProvider with invalid values for array arguments.
     *
     * @return array
     */
    public function dataProviderWithInvalidValuesForArrayArguments()
    {
        $invalidValues = array();
        $invalidValues['string'] = array('Hello');
        $invalidValues['integer'] = array(123);
        $invalidValues['boolean'] = array(true);
        $invalidValues['object'] = array(new \stdClass());
        $invalidValues['null'] = array(null);

        return $invalidValues;
    }

    /**
     * @test
     */
    public function getEventRecordInitiallyReturnsEmptyArray()
    {
        $this->assertSame(
            array(),
            $this->subject->getEventRecord()
        );
    }

    /**
     * @test
     */
    public function setEventRecordSetsEventRecord()
    {
        $array = array(
            0 => 'TestValue',
        );
        $this->subject->setEventRecord($array);

        $this->assertSame(
            $array,
            $this->subject->getEventRecord()
        );
    }

    /**
     * @test
     *
     * @param mixed $invalidArgument
     * @dataProvider dataProviderWithInvalidValuesForArrayArguments
     * @expectedException \PHPUnit_Framework_Error
     */
    public function setEventRecordWithInvalidArgumentsResultsInException($invalidArgument)
    {
        $this->subject->setEventRecord($invalidArgument);
    }

    /**
     * @test
     *
     * @param mixed $invalidArgument
     * @dataProvider dataProviderWithInvalidValuesForArrayArguments
     * @expectedException \PHPUnit_Framework_Error
     */
    public function createDayRelationsWithInvalidArgumentsResultsInException($invalidArgument)
    {
        $this->subject->setEventRecord($invalidArgument);
    }

    /**
     * @test
     */
    public function createDayRelationsWithEmptyEventDoesNotCallAnything()
    {
        /** @var \JWeiland\Events2\Service\DayRelations|\PHPUnit_Framework_MockObject_MockObject $dayRelations */
        $dayRelations = $this->getMock('JWeiland\\Events2\\Service\\DayRelations');
        $dayRelations->expects($this->never())->method('deleteAllRelatedRecords');
        $dayRelations->expects($this->never())->method('addDay');
        $dayRelations->createDayRelations(array());
    }

    /**
     * @test
     */
    public function createDayRelationsWithEmptyEventUidDoesNotCallAnything()
    {
        /** @var \JWeiland\Events2\Service\DayRelations|\PHPUnit_Framework_MockObject_MockObject $dayRelations */
        $dayRelations = $this->getMock('JWeiland\\Events2\\Service\\DayRelations');
        $dayRelations->expects($this->never())->method('deleteAllRelatedRecords');
        $dayRelations->expects($this->never())->method('addDay');
        $dayRelations->createDayRelations(array());
    }

    /**
     * @test
     */
    public function createDayRelationsWithEventConvertsCamelCaseToUnderscore()
    {
        $event = array(
            'uid' => 123,
            'title' => 'Test',
            'firstName' => 'Max',
            'last_name' => 'Mustermann',
            'whatALongKeyForAnArray' => 123,
            'UpperCaseAtTheBeginning' => 'Moin',
        );
        $expectedEvent = array(
            'uid' => 123,
            'title' => 'Test',
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
            'what_a_long_key_for_an_array' => 123,
            'upper_case_at_the_beginning' => 'Moin',
        );

        /** @var \JWeiland\Events2\Service\DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
        $dayGenerator = $this->getMock('JWeiland\\Events2\\Service\\DayGenerator', array('initialize'));
        $dayGenerator->expects($this->once())->method('initialize')->with($this->equalTo($expectedEvent));
        /** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $databaseConnection */
        $databaseConnection = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection');
        /** @var \JWeiland\Events2\Service\DayRelations|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $dayRelations */
        $dayRelations = $this->getAccessibleMock('JWeiland\\Events2\\Service\\DayRelations', array('dummy'));
        $dayRelations->injectDayGenerator($dayGenerator);
        $dayRelations->_set('databaseConnection', $databaseConnection);

        $dayRelations->createDayRelations($event);
    }

    /**
     * @test
     */
    public function createDayRelationsWithEventCallsDeleteAllRelatedRecordsWithCastedEventUid()
    {
        $event = array(
            'uid' => '123',
        );

        /** @var \JWeiland\Events2\Service\DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
        $dayGenerator = $this->getMock('JWeiland\\Events2\\Service\\DayGenerator');
        $dayGenerator->expects($this->once())->method('getDayStorage')->willReturn(array());
        /** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $databaseConnection */
        $databaseConnection = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection');
        /** @var \JWeiland\Events2\Service\DayRelations|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $dayRelations */
        $dayRelations = $this->getAccessibleMock('JWeiland\\Events2\\Service\\DayRelations', array('deleteAllRelatedRecords'));
        $dayRelations->injectDayGenerator($dayGenerator);
        $dayRelations->_set('databaseConnection', $databaseConnection);
        $dayRelations->expects($this->once())->method('deleteAllRelatedRecords')->with($this->identicalTo(123));

        $dayRelations->createDayRelations($event);
    }

    /**
     * @test
     */
    public function createDayRelationsWithEventCallsDeleteAllRelatedRecordsWithCorrectWhereClause()
    {
        $event = array(
            'uid' => 123,
        );

        /** @var \JWeiland\Events2\Service\DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
        $dayGenerator = $this->getMock('JWeiland\\Events2\\Service\\DayGenerator');
        $dayGenerator->expects($this->once())->method('getDayStorage')->willReturn(array());
        /** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $databaseConnection */
        $databaseConnection = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection');
        $databaseConnection->expects($this->once())->method('exec_DELETEquery')->with($this->equalTo('tx_events2_event_day_mm'), $this->equalTo('uid_local=123'));
        /** @var \JWeiland\Events2\Service\DayRelations|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $dayRelations */
        $dayRelations = $this->getAccessibleMock('JWeiland\\Events2\\Service\\DayRelations', array('dummy'));
        $dayRelations->injectDayGenerator($dayGenerator);
        $dayRelations->_set('databaseConnection', $databaseConnection);

        $dayRelations->createDayRelations($event);
    }

    /**
     * @test
     */
    public function createDayRelationsWithEventButNoDaysDoesNotCallAddDay()
    {
        $event = array(
            'uid' => 123,
            'title' => 'Test',
            'firstName' => 'Max',
            'last_name' => 'Mustermann',
            'whatALongKeyForAnArray' => 123,
            'UpperCaseAtTheBeginning' => 'Moin',
        );

        /** @var \JWeiland\Events2\Service\DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
        $dayGenerator = $this->getMock('JWeiland\\Events2\\Service\\DayGenerator');
        $dayGenerator->expects($this->once())->method('getDayStorage')->willReturn(array());
        /** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $databaseConnection */
        $databaseConnection = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection');
        /** @var \JWeiland\Events2\Service\DayRelations|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $dayRelations */
        $dayRelations = $this->getAccessibleMock('JWeiland\\Events2\\Service\\DayRelations', array('deleteAllRelatedRecords'));
        $dayRelations->injectDayGenerator($dayGenerator);
        $dayRelations->_set('databaseConnection', $databaseConnection);
        $dayRelations->expects($this->once())->method('deleteAllRelatedRecords')->with($this->equalTo($event['uid']));
        $dayRelations->expects($this->never())->method('addDay');

        $dayRelations->createDayRelations($event);
    }

    /**
     * @test
     */
    public function createDayRelationsWithEventAndRelatedDaysCallsAddDay()
    {
        $event = array(
            'uid' => 123,
            'title' => 'Test',
            'firstName' => 'Max',
            'last_name' => 'Mustermann',
            'whatALongKeyForAnArray' => 123,
            'UpperCaseAtTheBeginning' => 'Moin',
        );
        $yesterday = new \DateTime();
        $yesterday->modify('yesterday');
        $today = new \DateTime();
        $tomorrow = new \DateTime();
        $tomorrow->modify('tomorrow');

        $days = array();
        $days[$yesterday->format('U')] = $yesterday;
        $days[$today->format('U')] = $today;
        $days[$tomorrow->format('U')] = $tomorrow;

        /** @var \JWeiland\Events2\Service\DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
        $dayGenerator = $this->getMock('JWeiland\\Events2\\Service\\DayGenerator');
        $dayGenerator->expects($this->once())->method('getDayStorage')->willReturn($days);
        /** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $databaseConnection */
        $databaseConnection = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection');
        /** @var \JWeiland\Events2\Service\DayRelations|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $dayRelations */
        $dayRelations = $this->getAccessibleMock('JWeiland\\Events2\\Service\\DayRelations', array('deleteAllRelatedRecords', 'addDay'));
        $dayRelations->injectDayGenerator($dayGenerator);
        $dayRelations->_set('databaseConnection', $databaseConnection);
        $dayRelations->expects($this->once())->method('deleteAllRelatedRecords')->with($this->equalTo($event['uid']));
        $dayRelations->expects($this->at(1))->method('addDay')->with($this->equalTo($yesterday));
        $dayRelations->expects($this->at(2))->method('addDay')->with($this->equalTo($today));
        $dayRelations->expects($this->at(3))->method('addDay')->with($this->equalTo($tomorrow));

        $dayRelations->createDayRelations($event);
    }

    /**
     * @test
     */
    public function createDayRelationsWithEventAndRelatedDaysCallsUpdateQuery()
    {
        $event = array(
            'uid' => 123,
            'title' => 'Test',
            'firstName' => 'Max',
            'last_name' => 'Mustermann',
            'whatALongKeyForAnArray' => 123,
            'UpperCaseAtTheBeginning' => 'Moin',
        );
        $yesterday = new \DateTime();
        $yesterday->modify('yesterday');
        $today = new \DateTime();
        $tomorrow = new \DateTime();
        $tomorrow->modify('tomorrow');

        $days = array();
        $days[$yesterday->format('U')] = $yesterday;
        $days[$today->format('U')] = $today;
        $days[$tomorrow->format('U')] = $tomorrow;

        /** @var \JWeiland\Events2\Service\DayGenerator|\PHPUnit_Framework_MockObject_MockObject $dayGenerator */
        $dayGenerator = $this->getMock('JWeiland\\Events2\\Service\\DayGenerator');
        $dayGenerator->expects($this->once())->method('getDayStorage')->willReturn($days);
        /** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $databaseConnection */
        $databaseConnection = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection');
        $databaseConnection->expects($this->once())->method('exec_UPDATEquery')->with(
            $this->identicalTo('tx_events2_domain_model_event'),
            $this->identicalTo('uid=123'),
            $this->identicalTo(array('days' => 3))
        );
        /** @var \JWeiland\Events2\Service\DayRelations|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $dayRelations */
        $dayRelations = $this->getAccessibleMock('JWeiland\\Events2\\Service\\DayRelations', array('addDay', 'deleteAllRelatedRecords'));
        $dayRelations->injectDayGenerator($dayGenerator);
        $dayRelations->_set('databaseConnection', $databaseConnection);

        $dayRelations->createDayRelations($event);
    }

    /**
     * dataProvider with invalid values for DateTime arguments.
     *
     * @return array
     */
    public function dataProviderWithInvalidValuesForDateTimeArguments()
    {
        $invalidValues = array();
        $invalidValues['string'] = array('Hello');
        $invalidValues['integer'] = array(123);
        $invalidValues['boolean'] = array(true);
        $invalidValues['object'] = array(new \stdClass());
        $invalidValues['null'] = array(null);
        $invalidValues['array'] = array(array(0 => 123));

        return $invalidValues;
    }

    /**
     * @test
     *
     * @param mixed $invalidArgument
     * @dataProvider dataProviderWithInvalidValuesForDateTimeArguments
     * @expectedException \PHPUnit_Framework_Error
     */
    public function addDayWithInvalidArgumentsThrowsException($invalidArgument)
    {
        $this->subject->addDay($invalidArgument);
    }

    /**
     * @test
     */
    public function addDayConvertsDateTimeToMidnight()
    {
        $today = new \DateTime();
        $midnight = new \DateTime();
        $midnight->modify('midnight');

        /** @var \JWeiland\Events2\Utility\DateTimeUtility|\PHPUnit_Framework_MockObject_MockObject $dateTimeUtility */
        $dateTimeUtility = $this->getMock('JWeiland\\Events2\\Utility\\DateTimeUtility', array('dummy'));
        /** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $databaseConnection */
        $databaseConnection = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection');
        /** @var \JWeiland\Events2\Service\DayRelations|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $dayRelations */
        $dayRelations = $this->getAccessibleMock('JWeiland\\Events2\\Service\\DayRelations', array('addDayRecord', 'addRelation'));
        $dayRelations->injectDateTimeUtility($dateTimeUtility);
        $dayRelations->_set('databaseConnection', $databaseConnection);
        $dayRelations->expects($this->once())->method('addDayRecord')->with($this->equalTo($midnight));

        $dayRelations->addDay($today);
    }

    /**
     * @test
     */
    public function addDayWithBrokenQueryReturns0()
    {
        $today = new \DateTime();
        /** @var \JWeiland\Events2\Utility\DateTimeUtility|\PHPUnit_Framework_MockObject_MockObject $dateTimeUtility */
        $dateTimeUtility = $this->getMock('JWeiland\\Events2\\Utility\\DateTimeUtility', array('dummy'));
        /** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $databaseConnection */
        $databaseConnection = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection');
        $databaseConnection->expects($this->once())->method('exec_SELECTgetSingleRow')->willReturn(null);
        /** @var \JWeiland\Events2\Service\DayRelations|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $dayRelations */
        $dayRelations = $this->getAccessibleMock('JWeiland\\Events2\\Service\\DayRelations', array('addRelation'));
        $dayRelations->injectDateTimeUtility($dateTimeUtility);
        $dayRelations->_set('databaseConnection', $databaseConnection);

        $this->assertSame(
            0,
            $dayRelations->addDay($today)
        );
    }

    /**
     * @test
     */
    public function addDayWithDayInDatabaseReturnsDayUid()
    {
        $today = new \DateTime();
        /** @var \JWeiland\Events2\Utility\DateTimeUtility|\PHPUnit_Framework_MockObject_MockObject $dateTimeUtility */
        $dateTimeUtility = $this->getMock('JWeiland\\Events2\\Utility\\DateTimeUtility', array('dummy'));
        /** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $databaseConnection */
        $databaseConnection = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection');
        $databaseConnection->expects($this->once())->method('exec_SELECTgetSingleRow')->willReturn(array('uid' => '123'));
        /** @var \JWeiland\Events2\Service\DayRelations|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $dayRelations */
        $dayRelations = $this->getAccessibleMock('JWeiland\\Events2\\Service\\DayRelations', array('addRelation'));
        $dayRelations->injectDateTimeUtility($dateTimeUtility);
        $dayRelations->_set('databaseConnection', $databaseConnection);

        $this->assertSame(
            123,
            $dayRelations->addDay($today)
        );
    }

    /**
     * @test
     */
    public function addDayWithDayNotInDatabaseReturnsNewInsertedDayUid()
    {
        $today = new \DateTime();
        $midnight = new \DateTime();
        $midnight->modify('midnight');

        $event = array(
            'pid' => 23,
            'sys_language_uid' => 12,
        );

        $time = time();
        $fieldsWrittenToDatabase = array(
            'day' => (int) $midnight->format('U'),
            'tstamp' => $time,
            'pid' => 23,
            'crdate' => $time,
            'cruser_id' => (int) $GLOBALS['BE_USER']->user['uid'],
        );

        /** @var \JWeiland\Events2\Utility\DateTimeUtility|\PHPUnit_Framework_MockObject_MockObject $dateTimeUtility */
        $dateTimeUtility = $this->getMock('JWeiland\\Events2\\Utility\\DateTimeUtility', array('dummy'));
        /** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $databaseConnection */
        $databaseConnection = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection');
        $databaseConnection->expects($this->once())->method('exec_SELECTgetSingleRow')->willReturn(false);
        $databaseConnection->expects($this->once())->method('exec_INSERTquery')->with(
            $this->identicalTo('tx_events2_domain_model_day'),
            $this->identicalTo($fieldsWrittenToDatabase)
        );
        $databaseConnection->expects($this->once())->method('sql_insert_id')->willReturn('123');
        /** @var \JWeiland\Events2\Service\DayRelations|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $dayRelations */
        $dayRelations = $this->getAccessibleMock('JWeiland\\Events2\\Service\\DayRelations', array('addRelation'));
        $dayRelations->injectDateTimeUtility($dateTimeUtility);
        $dayRelations->_set('databaseConnection', $databaseConnection);
        $dayRelations->setEventRecord($event);

        $this->assertSame(
            123,
            $dayRelations->addDay($today)
        );
    }

    /**
     * @test
     */
    public function addDayAddsRelationAndReturnsNewDayUid()
    {
        $today = new \DateTime();
        $midnight = new \DateTime();
        $midnight->modify('midnight');

        $event = array(
            'uid' => 12,
        );

        $fieldsWrittenToDatabase = array(
            'uid_local' => 12,
            'uid_foreign' => 123,
            'sorting' => (int) $midnight->format('U'),
        );

        /** @var \JWeiland\Events2\Utility\DateTimeUtility|\PHPUnit_Framework_MockObject_MockObject $dateTimeUtility */
        $dateTimeUtility = $this->getMock('JWeiland\\Events2\\Utility\\DateTimeUtility', array('dummy'));
        /** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $databaseConnection */
        $databaseConnection = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection');
        $databaseConnection->expects($this->once())->method('exec_INSERTquery')->with(
            $this->identicalTo('tx_events2_event_day_mm'),
            $this->identicalTo($fieldsWrittenToDatabase)
        );
        /** @var \JWeiland\Events2\Service\DayRelations|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $dayRelations */
        $dayRelations = $this->getAccessibleMock('JWeiland\\Events2\\Service\\DayRelations', array('addDayRecord'));
        $dayRelations->injectDateTimeUtility($dateTimeUtility);
        $dayRelations->_set('databaseConnection', $databaseConnection);
        $dayRelations->setEventRecord($event);
        $dayRelations->expects($this->once())->method('addDayRecord')->willReturn(123);

        $this->assertSame(
            123,
            $dayRelations->addDay($today)
        );
    }

    /**
     * @test
     */
    public function addDayDoesNotAddAmountOfRelatedEventsToDayRecord()
    {
        $today = new \DateTime();
        $midnight = new \DateTime();
        $midnight->modify('midnight');

        /** @var \JWeiland\Events2\Utility\DateTimeUtility|\PHPUnit_Framework_MockObject_MockObject $dateTimeUtility */
        $dateTimeUtility = $this->getMock('JWeiland\\Events2\\Utility\\DateTimeUtility', array('dummy'));
        /** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $databaseConnection */
        $databaseConnection = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection');
        $databaseConnection->expects($this->once())->method('exec_SELECTcountRows')->with(
            $this->identicalTo('*'),
            $this->identicalTo('tx_events2_event_day_mm'),
            $this->identicalTo('uid_foreign=123')
        )->willReturn(0);
        $databaseConnection->expects($this->never())->method('exec_UPDATEquery');
        /** @var \JWeiland\Events2\Service\DayRelations|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $dayRelations */
        $dayRelations = $this->getAccessibleMock('JWeiland\\Events2\\Service\\DayRelations', array('addDayRecord', 'addRelation'));
        $dayRelations->injectDateTimeUtility($dateTimeUtility);
        $dayRelations->_set('databaseConnection', $databaseConnection);
        $dayRelations->expects($this->once())->method('addDayRecord')->willReturn(123);

        $this->assertSame(
            123,
            $dayRelations->addDay($today)
        );
    }

    /**
     * @test
     */
    public function addDayAddsAmountOfRelatedEventsToDayRecord()
    {
        $today = new \DateTime();
        $midnight = new \DateTime();
        $midnight->modify('midnight');

        /** @var \JWeiland\Events2\Utility\DateTimeUtility|\PHPUnit_Framework_MockObject_MockObject $dateTimeUtility */
        $dateTimeUtility = $this->getMock('JWeiland\\Events2\\Utility\\DateTimeUtility', array('dummy'));
        /** @var \TYPO3\CMS\Core\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject $databaseConnection */
        $databaseConnection = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection');
        $databaseConnection->expects($this->once())->method('exec_SELECTcountRows')->with(
            $this->identicalTo('*'),
            $this->identicalTo('tx_events2_event_day_mm'),
            $this->identicalTo('uid_foreign=123')
        )->willReturn(7);
        $databaseConnection->expects($this->once())->method('exec_UPDATEquery')->with(
            $this->identicalTo('tx_events2_domain_model_day'),
            $this->identicalTo('uid=123'),
            $this->identicalTo(array('events' => 7))
        );
        /** @var \JWeiland\Events2\Service\DayRelations|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $dayRelations */
        $dayRelations = $this->getAccessibleMock('JWeiland\\Events2\\Service\\DayRelations', array('addDayRecord', 'addRelation'));
        $dayRelations->injectDateTimeUtility($dateTimeUtility);
        $dayRelations->_set('databaseConnection', $databaseConnection);
        $dayRelations->expects($this->once())->method('addDayRecord')->willReturn(123);

        $this->assertSame(
            123,
            $dayRelations->addDay($today)
        );
    }
}
