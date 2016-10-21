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
use JWeiland\Events2\Service\DayGenerator;
use JWeiland\Events2\Service\DayRelations;
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Tests\AccessibleObjectInterface;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case for class \JWeiland\Events2\Service\DayRelations.
 *
 * @author Stefan Froemken <projects@jweiland.net>
 */
class DayRelationsTest extends UnitTestCase
{
    /**
     * @var DayRelations|\PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface
     */
    protected $subject;
    
    /**
     * @var DayGenerator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dayGenerator;
    
    /**
     * @var DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $databaseConnection;
    
    /**
     * @var DateTimeUtility|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dateTimeUtility;
    
    /**
     * set up.
     */
    public function setUp()
    {
        $this->databaseConnection = $this->getMock(DatabaseConnection::class);
        $this->dayGenerator = $this->getMock(DayGenerator::class);
        $this->dateTimeUtility = new DateTimeUtility();

        $this->subject = $this->getAccessibleMock(DayRelations::class, array('dummy'));
        $this->subject->_set('databaseConnection', $this->databaseConnection);
        $this->subject->injectDayGenerator($this->dayGenerator);
        $this->subject->injectDateTimeUtility($this->dateTimeUtility);
    }

    /**
     * tear down.
     */
    public function tearDown()
    {
        unset($this->subject);
        unset($this->dayGenerator);
        unset($this->databaseConnection);
    }

    /**
     * @test
     */
    public function createDayRelationsWithEmptyEventDoesNotCallAnything()
    {
        $this->subject
            ->expects($this->never())
            ->method('deleteAllRelatedRecords');
        $this->subject
            ->expects($this->never())
            ->method('addDay');
        
        $this->subject->createDayRelations(array());
    }

    /**
     * @test
     */
    public function createDayRelationsWithEmptyEventUidDoesNotCallAnything()
    {
        $this->subject
            ->expects($this->never())
            ->method('deleteAllRelatedRecords');
        $this->subject
            ->expects($this->never())
            ->method('addDay');
        
        $this->subject->createDayRelations(array());
    }

    /**
     * @test
     */
    public function createDayRelationsWithEventConvertsCamelCaseToUnderscore()
    {
        $event = array(
            'uid' => 123,
            'firstName' => 'Max',
            'last_name' => 'Mustermann',
            'whatALongKeyForAnArray' => 123,
            'UpperCaseAtTheBeginning' => 'Moin',
        );
        $expectedEvent = array(
            'uid' => 123,
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
            'what_a_long_key_for_an_array' => 123,
            'upper_case_at_the_beginning' => 'Moin',
        );
    
        $this->dayGenerator
            ->expects($this->once())
            ->method('initialize')
            ->with($this->equalTo($expectedEvent));
        $this->dayGenerator
            ->expects($this->once())
            ->method('getDayStorage')
            ->willReturn(array());
        
        $this->subject->createDayRelations($event);
    }

    /**
     * @test
     */
    public function createDayRelationsWithEventCallsDeleteAllRelatedRecordsWithCastedEventUid()
    {
        $event = array(
            'uid' => '123',
        );

        $this->dayGenerator
            ->expects($this->once())
            ->method('getDayStorage')
            ->willReturn(array());
    
        $this->databaseConnection
            ->expects($this->at(0))
            ->method('exec_DELETEquery')
            ->with(
                $this->equalTo('tx_events2_event_day_mm'),
                $this->equalTo('uid_local=123')
            );
        $this->databaseConnection
            ->expects($this->at(1))
            ->method('exec_DELETEquery')
            ->with(
                $this->equalTo('tx_events2_domain_model_day'),
                $this->equalTo('event=123')
            );

        $this->subject->createDayRelations($event);
    }

    /**
     * @test
     */
    public function createDayRelationsWithEventCallsDeleteAllRelatedRecordsWithCorrectWhereClause()
    {
        $event = array(
            'uid' => 123,
        );

        $this->dayGenerator
            ->expects($this->once())
            ->method('getDayStorage')
            ->willReturn(array());
        
        $this->databaseConnection
            ->expects($this->at(0))
            ->method('exec_DELETEquery')
            ->with(
                $this->equalTo('tx_events2_event_day_mm'),
                $this->equalTo('uid_local=123')
            );
        $this->databaseConnection
            ->expects($this->at(1))
            ->method('exec_DELETEquery')
            ->with(
                $this->equalTo('tx_events2_domain_model_day'),
                $this->equalTo('event=123')
            );
        
        $this->subject->createDayRelations($event);
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

        $this->dayGenerator
            ->expects($this->once())
            ->method('getDayStorage')
            ->willReturn(array());

        $this->subject
            ->expects($this->never())
            ->method('addDay');

        $this->subject->createDayRelations($event);
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

        $this->dayGenerator
            ->expects($this->once())
            ->method('getDayStorage')
            ->willReturn($days);
    
        $this->databaseConnection
            ->expects($this->at(2))
            ->method('exec_INSERTquery')
            ->with(
                $this->equalTo('tx_events2_domain_model_day'),
                $this->contains($yesterday->format('U'))
            );
        $this->databaseConnection
            ->expects($this->at(4))
            ->method('exec_INSERTquery')
            ->with(
                $this->equalTo('tx_events2_domain_model_day'),
                $this->contains($today->format('U'))
            );
        $this->databaseConnection
            ->expects($this->at(6))
            ->method('exec_INSERTquery')
            ->with(
                $this->equalTo('tx_events2_domain_model_day'),
                $this->contains($tomorrow->format('U'))
            );

        $this->subject->createDayRelations($event);
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

        $this->dayGenerator
            ->expects($this->once())
            ->method('getDayStorage')
            ->willReturn($days);

        $this->databaseConnection
            ->expects($this->once())
            ->method('exec_UPDATEquery')
            ->with(
                $this->identicalTo('tx_events2_domain_model_event'),
                $this->identicalTo('uid=123'),
                $this->identicalTo(array('days' => 3))
            );
        
        $this->subject->createDayRelations($event);
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
    public function addDayAddsRelationAndReturnsNewDayUid()
    {
        $today = new \DateTime();
        $todayMidnight = new \DateTime();
        $todayMidnight->modify('midnight');

        $fieldsWrittenToDatabase = array(
            'uid_local' => 12,
            'uid_foreign' => 123,
            'sorting' => (int)$todayMidnight->format('U'),
        );
        
        $this->databaseConnection
            ->expects($this->once())
            ->method('sql_insert_id')
            ->willReturn(123);

        $this->databaseConnection
            ->expects($this->at(2))
            ->method('exec_INSERTquery')
            ->with(
                $this->identicalTo('tx_events2_event_day_mm'),
                $this->identicalTo($fieldsWrittenToDatabase)
            );
        
        $this->subject->_set('eventRecord', array('uid' => 12));

        $this->assertSame(
            123,
            $this->subject->addDay($today)
        );
    }
}
