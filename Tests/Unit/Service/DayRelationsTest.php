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
use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Service\DayGenerator;
use JWeiland\Events2\Service\DayRelations;
use JWeiland\Events2\Utility\DateTimeUtility;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
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
     * @var ExtConf|ObjectProphecy
     */
    protected $extConfProphecy;

    /**
     * @var DatabaseConnection|ObjectProphecy
     */
    protected $dbProphecy;

    /**
     * set up.
     */
    public function setUp()
    {
        $this->extConfProphecy = $this->prophesize(ExtConf::class);
        $this->extConfProphecy->getRecurringPast()->willReturn(3);
        $this->extConfProphecy->getRecurringFuture()->willReturn(6);
        $this->extConfProphecy->getMergeEvents()->willReturn(false);

        $this->dbProphecy = $this->prophesize(DatabaseConnection::class);
        $GLOBALS['TYPO3_DB'] = $this->dbProphecy->reveal();

        // needed for getItemsFromTca in DayGenerator
        $GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['xth']['config']['items'] = [
            ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.xth.first', 'first'],
            ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.xth.second', 'second'],
            ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.xth.third', 'third'],
            ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.xth.fourth', 'fourth'],
            ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.xth.fifth', 'fifth'],
        ];
        $GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['weekday']['config']['items'] = [
            ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday.monday', 'monday'],
            ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday.tuesday', 'tuesday'],
            ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday.wednesday', 'wednesday'],
            ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday.thursday', 'thursday'],
            ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday.friday', 'friday'],
            ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday.saturday', 'saturday'],
            ['LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.weekday.sunday', 'sunday'],
        ];

        $dayGenerator = new DayGenerator();
        $dayGenerator->injectExtConf($this->extConfProphecy->reveal());
        $dayGenerator->injectDateTimeUtility(new DateTimeUtility());

        $this->subject = new DayRelations();
        $this->subject->initializeObject(); // sets TYPO3_DB
        $this->subject->injectExtConf($this->extConfProphecy->reveal());
        $this->subject->injectDayGenerator($dayGenerator);
        $this->subject->injectDateTimeUtility(new DateTimeUtility());
    }

    /**
     * tear down.
     */
    public function tearDown()
    {
        $this->dbProphecy->checkProphecyMethodsPredictions();
    }

    /**
     * @test
     */
    public function createDayRelationsWithEmptyEventWillNeverCallAnyQuery()
    {
        $this->dbProphecy->exec_INSERTquery(Argument::cetera())->shouldNotBeCalled();
        $this->dbProphecy->exec_DELETEquery(Argument::cetera())->shouldNotBeCalled();
        $this->dbProphecy->exec_UPDATEquery(Argument::cetera())->shouldNotBeCalled();

        $this->subject->createDayRelations(array());
    }

    /**
     * @test
     */
    public function createDayRelationsWithNonUidWillNeverCallAnyQuery()
    {
        $this->dbProphecy->exec_INSERTquery(Argument::cetera())->shouldNotBeCalled();
        $this->dbProphecy->exec_DELETEquery(Argument::cetera())->shouldNotBeCalled();
        $this->dbProphecy->exec_UPDATEquery(Argument::cetera())->shouldNotBeCalled();

        $this->subject->createDayRelations(array(
            'title' => 'Test',
            'pid' => '123'
        ));
    }

    /**
     * @test
     */
    public function createDayRelationsWithNonPidWillNeverCallAnyQuery()
    {
        $this->dbProphecy->exec_INSERTquery(Argument::cetera())->shouldNotBeCalled();
        $this->dbProphecy->exec_DELETEquery(Argument::cetera())->shouldNotBeCalled();
        $this->dbProphecy->exec_UPDATEquery(Argument::cetera())->shouldNotBeCalled();

        $this->subject->createDayRelations(array(
            'title' => 'Test',
            'uid' => '123'
        ));
    }

    /**
     * @test
     */
    public function createDayRelationsWithEventConvertsCamelCaseToUnderscore()
    {
        $event = array(
            'uid' => 123,
            'pid' => 321,
            'firstName' => 'Max',
            'last_name' => 'Mustermann',
            'whatALongKeyForAnArray' => 123,
            'UpperCaseAtTheBeginning' => 'Moin',
        );
        $expectedEvent = array(
            'uid' => 123,
            'pid' => 321,
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
            'what_a_long_key_for_an_array' => 123,
            'upper_case_at_the_beginning' => 'Moin',
        );

        /** @var DayGenerator|ObjectProphecy $dayGenerator */
        $dayGenerator = $this->prophesize(DayGenerator::class);
        $dayGenerator->initialize($expectedEvent)->shouldBeCalled();
        $dayGenerator->getDayStorage()->shouldBeCalled()->willReturn([]);
        $this->subject->injectDayGenerator($dayGenerator->reveal());

        $this->subject->createDayRelations($event);
    }

    /**
     * An event with none configured start/end dates will result in zero days
     * So all related days have to be deleted
     * But addDay/INSERT will not be called
     *
     * @test
     */
    public function createDayRelationsWithNonConfiguredEventDoesNotCallAddDay()
    {
        $event = array(
            'uid' => 123,
            'pid' => 321,
        );

        $this->dbProphecy->exec_DELETEquery('tx_events2_event_day_mm', 'uid_local=123')->shouldBeCalled();
        $this->dbProphecy->exec_DELETEquery('tx_events2_domain_model_day', 'event=123')->shouldBeCalled();
        $this->dbProphecy->exec_UPDATEquery('tx_events2_domain_model_event', 'uid=123', ['days' => 0])->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery(Argument::cetera())->shouldNotBeCalled();

        $this->subject->createDayRelations($event);
    }

    /**
     * Test a simple recurring event with no time/exception and whatever records
     * In that case day, day_time and sort_day_time will all be equal
     *
     * @test
     */
    public function createDayRelationsWithRecurringEvent()
    {
        $yesterday = new \DateTime();
        $yesterday->modify('yesterday midnight');
        $today = new \DateTime();
        $today->modify('midnight');
        $tomorrow = new \DateTime();
        $tomorrow->modify('tomorrow midnight');

        $event = array(
            'uid' => 123,
            'pid' => 321,
            'event_type' => 'recurring',
            'event_begin' => $yesterday->format('U'),
            'recurring_end' => $tomorrow->format('U'),
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 0,
            'exceptions' => 0,
        );

        $this->dbProphecy->sql_insert_id()->shouldBeCalled();
        $this->dbProphecy->exec_DELETEquery(Argument::cetera())->shouldBeCalled();
        $this->dbProphecy->exec_UPDATEquery(Argument::cetera())->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$yesterday->format('U')),
            Argument::withEntry('day_time', (int)$yesterday->format('U')),
            Argument::withEntry('sort_day_time', (int)$yesterday->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$today->format('U')),
            Argument::withEntry('day_time', (int)$today->format('U')),
            Argument::withEntry('sort_day_time', (int)$today->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$tomorrow->format('U')),
            Argument::withEntry('day_time', (int)$tomorrow->format('U')),
            Argument::withEntry('sort_day_time', (int)$tomorrow->format('U'))
        ))->shouldBeCalled();

        $this->subject->createDayRelations($event);
    }

    /**
     * Test a recurring event with time record which is equal for all days
     * In that case day differs from day_time and sort_day_time
     * day_time and sort_day_time are equal
     *
     * @test
     */
    public function createDayRelationsWithRecurringEventAndTime()
    {
        $yesterday = new \DateTime();
        $yesterday->modify('yesterday midnight');
        $yesterdayLaunch = new \DateTime();
        $yesterdayLaunch->modify('yesterday 12:30');
        $today = new \DateTime();
        $today->modify('midnight');
        $todayLaunch = new \DateTime();
        $todayLaunch->modify('12:30');
        $tomorrow = new \DateTime();
        $tomorrow->modify('tomorrow midnight');
        $tomorrowLaunch = new \DateTime();
        $tomorrowLaunch->modify('tomorrow 12:30');

        $event = array(
            'uid' => 123,
            'pid' => 321,
            'event_type' => 'recurring',
            'event_begin' => $yesterday->format('U'),
            'recurring_end' => $tomorrow->format('U'),
            'event_time' => [
                ['time_begin' => '12:30']
            ],
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 0,
            'exceptions' => 0,
        );

        $this->dbProphecy->sql_insert_id()->shouldBeCalled();
        $this->dbProphecy->exec_DELETEquery(Argument::cetera())->shouldBeCalled();
        $this->dbProphecy->exec_UPDATEquery(Argument::cetera())->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$yesterday->format('U')),
            Argument::withEntry('day_time', (int)$yesterdayLaunch->format('U')),
            Argument::withEntry('sort_day_time', (int)$yesterdayLaunch->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$today->format('U')),
            Argument::withEntry('day_time', (int)$todayLaunch->format('U')),
            Argument::withEntry('sort_day_time', (int)$todayLaunch->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$tomorrow->format('U')),
            Argument::withEntry('day_time', (int)$tomorrowLaunch->format('U')),
            Argument::withEntry('sort_day_time', (int)$tomorrowLaunch->format('U'))
        ))->shouldBeCalled();

        $this->subject->createDayRelations($event);
    }

    /**
     * Test a recurring event with multiple time records for same day
     * In that case day is current day at midnight
     * day_time is current day morning and within a second record current day evening
     * sort_day_time is current day morning and within a second record current day evening
     *
     * @test
     */
    public function createDayRelationsWithRecurringEventAndMultipleTimesAtSameDay()
    {
        $yesterday = new \DateTime();
        $yesterday->modify('yesterday midnight');
        $yesterdayMorning = new \DateTime();
        $yesterdayMorning->modify('yesterday 08:00');
        $yesterdayEvening = new \DateTime();
        $yesterdayEvening->modify('yesterday 20:15');
        $today = new \DateTime();
        $today->modify('midnight');
        $todayMorning = new \DateTime();
        $todayMorning->modify('08:00');
        $todayEvening = new \DateTime();
        $todayEvening->modify('20:15');
        $tomorrow = new \DateTime();
        $tomorrow->modify('tomorrow midnight');
        $tomorrowMorning = new \DateTime();
        $tomorrowMorning->modify('tomorrow 08:00');
        $tomorrowEvening = new \DateTime();
        $tomorrowEvening->modify('tomorrow 20:15');

        $event = array(
            'uid' => 123,
            'pid' => 321,
            'event_type' => 'recurring',
            'event_begin' => $yesterday->format('U'),
            'recurring_end' => $tomorrow->format('U'),
            'event_time' => [
                ['time_begin' => '08:00']
            ],
            'multiple_times' => [
                ['time_begin' => '20:15']
            ],
            'same_day' => 1,
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 0,
            'exceptions' => 0,
        );

        $this->dbProphecy->sql_insert_id()->shouldBeCalled();
        $this->dbProphecy->exec_DELETEquery(Argument::cetera())->shouldBeCalled();
        $this->dbProphecy->exec_UPDATEquery(Argument::cetera())->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$yesterday->format('U')),
            Argument::withEntry('day_time', (int)$yesterdayMorning->format('U')),
            Argument::withEntry('sort_day_time', (int)$yesterdayMorning->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$yesterday->format('U')),
            Argument::withEntry('day_time', (int)$yesterdayEvening->format('U')),
            Argument::withEntry('sort_day_time', (int)$yesterdayEvening->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$today->format('U')),
            Argument::withEntry('day_time', (int)$todayMorning->format('U')),
            Argument::withEntry('sort_day_time', (int)$todayMorning->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$today->format('U')),
            Argument::withEntry('day_time', (int)$todayEvening->format('U')),
            Argument::withEntry('sort_day_time', (int)$todayEvening->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$tomorrow->format('U')),
            Argument::withEntry('day_time', (int)$tomorrowMorning->format('U')),
            Argument::withEntry('sort_day_time', (int)$tomorrowMorning->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$tomorrow->format('U')),
            Argument::withEntry('day_time', (int)$tomorrowEvening->format('U')),
            Argument::withEntry('sort_day_time', (int)$tomorrowEvening->format('U'))
        ))->shouldBeCalled();

        $this->subject->createDayRelations($event);
    }

    /**
     * Test a recurring event with multiple time records for same day
     * Special handling for recurring events where we do merge events at same time
     * In that case day is current day at midnight
     * day_time is current day morning and within a second record current day evening
     * But sort_day_time is always current day morning, as we display all times as one record in FE
     *
     * @test
     */
    public function createDayRelationsWithRecurringEventAndMultipleTimesAtSameDayMerge()
    {
        $this->extConfProphecy->getMergeEvents()->willReturn(true);

        $yesterday = new \DateTime();
        $yesterday->modify('yesterday midnight');
        $yesterdayMorning = new \DateTime();
        $yesterdayMorning->modify('yesterday 08:00');
        $yesterdayEvening = new \DateTime();
        $yesterdayEvening->modify('yesterday 20:15');
        $today = new \DateTime();
        $today->modify('midnight');
        $todayMorning = new \DateTime();
        $todayMorning->modify('08:00');
        $todayEvening = new \DateTime();
        $todayEvening->modify('20:15');
        $tomorrow = new \DateTime();
        $tomorrow->modify('tomorrow midnight');
        $tomorrowMorning = new \DateTime();
        $tomorrowMorning->modify('tomorrow 08:00');
        $tomorrowEvening = new \DateTime();
        $tomorrowEvening->modify('tomorrow 20:15');

        $event = array(
            'uid' => 123,
            'pid' => 321,
            'event_type' => 'recurring',
            'event_begin' => $yesterday->format('U'),
            'recurring_end' => $tomorrow->format('U'),
            'event_time' => [
                ['time_begin' => '08:00']
            ],
            'multiple_times' => [
                ['time_begin' => '20:15']
            ],
            'same_day' => 1,
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 0,
            'exceptions' => 0,
        );

        $this->dbProphecy->sql_insert_id()->shouldBeCalled();
        $this->dbProphecy->exec_DELETEquery(Argument::cetera())->shouldBeCalled();
        $this->dbProphecy->exec_UPDATEquery(Argument::cetera())->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$yesterday->format('U')),
            Argument::withEntry('day_time', (int)$yesterdayMorning->format('U')),
            Argument::withEntry('sort_day_time', (int)$yesterdayMorning->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$yesterday->format('U')),
            Argument::withEntry('day_time', (int)$yesterdayEvening->format('U')),
            Argument::withEntry('sort_day_time', (int)$yesterdayMorning->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$today->format('U')),
            Argument::withEntry('day_time', (int)$todayMorning->format('U')),
            Argument::withEntry('sort_day_time', (int)$todayMorning->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$today->format('U')),
            Argument::withEntry('day_time', (int)$todayEvening->format('U')),
            Argument::withEntry('sort_day_time', (int)$todayMorning->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$tomorrow->format('U')),
            Argument::withEntry('day_time', (int)$tomorrowMorning->format('U')),
            Argument::withEntry('sort_day_time', (int)$tomorrowMorning->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$tomorrow->format('U')),
            Argument::withEntry('day_time', (int)$tomorrowEvening->format('U')),
            Argument::withEntry('sort_day_time', (int)$tomorrowMorning->format('U'))
        ))->shouldBeCalled();

        $this->subject->createDayRelations($event);
    }

    /**
     * Test is the same test as above.
     * But getRecurringPast will return 0 month.
     * So only future events are allowed
     *
     * @test
     */
    public function createDayRelationsWithRecurringEventAndMultipleTimesAtSameDayFuture()
    {
        $this->extConfProphecy->getRecurringPast()->willReturn(0);

        $yesterday = new \DateTime();
        $yesterday->modify('yesterday midnight');
        $yesterdayMorning = new \DateTime();
        $yesterdayMorning->modify('yesterday 08:00');
        $yesterdayEvening = new \DateTime();
        $yesterdayEvening->modify('yesterday 20:15');
        $today = new \DateTime();
        $today->modify('midnight');
        $todayMorning = new \DateTime();
        $todayMorning->modify('08:00');
        $todayEvening = new \DateTime();
        $todayEvening->modify('20:15');
        $tomorrow = new \DateTime();
        $tomorrow->modify('tomorrow midnight');
        $tomorrowMorning = new \DateTime();
        $tomorrowMorning->modify('tomorrow 08:00');
        $tomorrowEvening = new \DateTime();
        $tomorrowEvening->modify('tomorrow 20:15');

        $event = array(
            'uid' => 123,
            'pid' => 321,
            'event_type' => 'recurring',
            'event_begin' => $yesterday->format('U'),
            'recurring_end' => $tomorrow->format('U'),
            'event_time' => [
                ['time_begin' => '08:00']
            ],
            'multiple_times' => [
                ['time_begin' => '20:15']
            ],
            'same_day' => 1,
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 0,
            'exceptions' => 0,
        );

        $this->dbProphecy->sql_insert_id()->shouldBeCalled();
        $this->dbProphecy->exec_DELETEquery(Argument::cetera())->shouldBeCalled();
        $this->dbProphecy->exec_UPDATEquery(Argument::cetera())->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$today->format('U')),
            Argument::withEntry('day_time', (int)$todayMorning->format('U')),
            Argument::withEntry('sort_day_time', (int)$todayMorning->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$today->format('U')),
            Argument::withEntry('day_time', (int)$todayEvening->format('U')),
            Argument::withEntry('sort_day_time', (int)$todayEvening->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$tomorrow->format('U')),
            Argument::withEntry('day_time', (int)$tomorrowMorning->format('U')),
            Argument::withEntry('sort_day_time', (int)$tomorrowMorning->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$tomorrow->format('U')),
            Argument::withEntry('day_time', (int)$tomorrowEvening->format('U')),
            Argument::withEntry('sort_day_time', (int)$tomorrowEvening->format('U'))
        ))->shouldBeCalled();

        $this->subject->createDayRelations($event);
    }

    /**
     * Test is the same test as above.
     * But getRecurringPast will return 0 month.
     * So only future events are allowed
     *
     * Activate special feature for recurring events that we merge events at same day
     *
     * @test
     */
    public function createDayRelationsWithRecurringEventAndMultipleTimesAtSameDayFutureMerge()
    {
        $this->extConfProphecy->getMergeEvents()->willReturn(true);
        $this->extConfProphecy->getRecurringPast()->willReturn(0);

        $yesterday = new \DateTime();
        $yesterday->modify('yesterday midnight');
        $yesterdayMorning = new \DateTime();
        $yesterdayMorning->modify('yesterday 08:00');
        $yesterdayEvening = new \DateTime();
        $yesterdayEvening->modify('yesterday 20:15');
        $today = new \DateTime();
        $today->modify('midnight');
        $todayMorning = new \DateTime();
        $todayMorning->modify('08:00');
        $todayEvening = new \DateTime();
        $todayEvening->modify('20:15');
        $tomorrow = new \DateTime();
        $tomorrow->modify('tomorrow midnight');
        $tomorrowMorning = new \DateTime();
        $tomorrowMorning->modify('tomorrow 08:00');
        $tomorrowEvening = new \DateTime();
        $tomorrowEvening->modify('tomorrow 20:15');

        $event = array(
            'uid' => 123,
            'pid' => 321,
            'event_type' => 'recurring',
            'event_begin' => $yesterday->format('U'),
            'recurring_end' => $tomorrow->format('U'),
            'event_time' => [
                ['time_begin' => '08:00']
            ],
            'multiple_times' => [
                ['time_begin' => '20:15']
            ],
            'same_day' => 1,
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 0,
            'exceptions' => 0,
        );

        $this->dbProphecy->sql_insert_id()->shouldBeCalled();
        $this->dbProphecy->exec_DELETEquery(Argument::cetera())->shouldBeCalled();
        $this->dbProphecy->exec_UPDATEquery(Argument::cetera())->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$today->format('U')),
            Argument::withEntry('day_time', (int)$todayMorning->format('U')),
            Argument::withEntry('sort_day_time', (int)$todayMorning->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$today->format('U')),
            Argument::withEntry('day_time', (int)$todayEvening->format('U')),
            Argument::withEntry('sort_day_time', (int)$todayMorning->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$tomorrow->format('U')),
            Argument::withEntry('day_time', (int)$tomorrowMorning->format('U')),
            Argument::withEntry('sort_day_time', (int)$tomorrowMorning->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$tomorrow->format('U')),
            Argument::withEntry('day_time', (int)$tomorrowEvening->format('U')),
            Argument::withEntry('sort_day_time', (int)$tomorrowMorning->format('U'))
        ))->shouldBeCalled();

        $this->subject->createDayRelations($event);
    }

    /**
     * Test a recurring event with different times for weekday
     * In that case day is current day at midnight
     * day_time and sort_day_time are equal
     *
     * @test
     */
    public function createDayRelationsWithRecurringEventAndDifferentTimes()
    {
        $tuesday = new \DateTime();
        $tuesday->modify('last tuesday midnight');
        $tuesdayMorning = clone $tuesday;
        $tuesdayMorning->modify('08:00');
        $wednesday = clone $tuesday;
        $wednesday->modify('+1 day');
        $wednesdayEvening = clone $wednesday;
        $wednesdayEvening->modify('20:15');
        $thursday = clone $wednesday;
        $thursday->modify('+1 day');
        $thursdayMorning = clone $thursday;
        $thursdayMorning->modify('08:00');

        $event = array(
            'uid' => 123,
            'pid' => 321,
            'event_type' => 'recurring',
            'event_begin' => $tuesday->format('U'),
            'recurring_end' => $thursday->format('U'),
            'event_time' => [
                ['time_begin' => '08:00']
            ],
            'different_times' => [
                [
                    'weekday' => 'wednesday',
                    'time_begin' => '20:15'
                ]
            ],
            'same_day' => 1,
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 0,
            'exceptions' => 0,
        );

        $this->dbProphecy->sql_insert_id()->shouldBeCalled();
        $this->dbProphecy->exec_DELETEquery(Argument::cetera())->shouldBeCalled();
        $this->dbProphecy->exec_UPDATEquery(Argument::cetera())->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$tuesday->format('U')),
            Argument::withEntry('day_time', (int)$tuesdayMorning->format('U')),
            Argument::withEntry('sort_day_time', (int)$tuesdayMorning->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$wednesday->format('U')),
            Argument::withEntry('day_time', (int)$wednesdayEvening->format('U')),
            Argument::withEntry('sort_day_time', (int)$wednesdayEvening->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$thursday->format('U')),
            Argument::withEntry('day_time', (int)$thursdayMorning->format('U')),
            Argument::withEntry('sort_day_time', (int)$thursdayMorning->format('U'))
        ))->shouldBeCalled();

        $this->subject->createDayRelations($event);
    }

    /**
     * Test a recurring event with exception times for one special day
     * In that case day is current day at midnight
     * day_time and sort_day_time are equal
     *
     * @test
     */
    public function createDayRelationsWithRecurringEventAndExceptionTimes()
    {
        $tuesday = new \DateTime();
        $tuesday->modify('last tuesday midnight');
        $wednesday = clone $tuesday;
        $wednesday->modify('+1 day');
        $wednesdayEvening = clone $wednesday;
        $wednesdayEvening->modify('20:15');
        $thursday = clone $wednesday;
        $thursday->modify('+1 day');
        $friday = clone $thursday;
        $friday->modify('+1 day');
        $fridayLaunch = clone $friday;
        $fridayLaunch->modify('12:30');

        $event = array(
            'uid' => 123,
            'pid' => 321,
            'event_type' => 'recurring',
            'event_begin' => $tuesday->format('U'),
            'recurring_end' => $thursday->format('U'),
            'exceptions' => [
                [
                    'exception_type' => 'Time',
                    'exception_date' => $wednesday->format('U'),
                    'exception_time' => [
                        ['time_begin' => '20:15']
                    ]
                ],
                [
                    'exception_type' => 'Add',
                    'exception_date' => $friday->format('U'),
                    'exception_time' => [
                        ['time_begin' => '12:30']
                    ]
                ],
            ],
            'same_day' => 1,
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 0,
        );

        $this->dbProphecy->sql_insert_id()->shouldBeCalled();
        $this->dbProphecy->exec_DELETEquery(Argument::cetera())->shouldBeCalled();
        $this->dbProphecy->exec_UPDATEquery(Argument::cetera())->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$tuesday->format('U')),
            Argument::withEntry('day_time', (int)$tuesday->format('U')),
            Argument::withEntry('sort_day_time', (int)$tuesday->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$wednesday->format('U')),
            Argument::withEntry('day_time', (int)$wednesdayEvening->format('U')),
            Argument::withEntry('sort_day_time', (int)$wednesdayEvening->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$thursday->format('U')),
            Argument::withEntry('day_time', (int)$thursday->format('U')),
            Argument::withEntry('sort_day_time', (int)$thursday->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$friday->format('U')),
            Argument::withEntry('day_time', (int)$fridayLaunch->format('U')),
            Argument::withEntry('sort_day_time', (int)$fridayLaunch->format('U'))
        ))->shouldBeCalled();

        $this->subject->createDayRelations($event);
    }

    /**
     * Test a recurring event with multiple exception times for one special day
     * In that case day is current day at midnight
     * day_time is current day + specified time
     * sort_day_time is current day + specified time
     *
     * @test
     */
    public function createDayRelationsWithRecurringEventAndMultipleExceptionTimes()
    {
        $tuesday = new \DateTime();
        $tuesday->modify('last tuesday midnight');
        $wednesday = clone $tuesday;
        $wednesday->modify('+1 day');
        $wednesdayMorning = clone $wednesday;
        $wednesdayMorning->modify('08:00');
        $wednesdayEvening = clone $wednesday;
        $wednesdayEvening->modify('20:15');
        $thursday = clone $wednesday;
        $thursday->modify('+1 day');
        $friday = clone $thursday;
        $friday->modify('+1 day');
        $fridayLaunch = clone $friday;
        $fridayLaunch->modify('12:30');

        $event = array(
            'uid' => 123,
            'pid' => 321,
            'event_type' => 'recurring',
            'event_begin' => $tuesday->format('U'),
            'recurring_end' => $thursday->format('U'),
            'exceptions' => [
                [
                    'exception_type' => 'Add',
                    'exception_date' => $wednesday->format('U'),
                    'exception_time' => [
                        ['time_begin' => '08:00']
                    ]
                ],
                [
                    'exception_type' => 'Time',
                    'exception_date' => $wednesday->format('U'),
                    'exception_time' => [
                        ['time_begin' => '20:15']
                    ]
                ],
                [
                    'exception_type' => 'Add',
                    'exception_date' => $friday->format('U'),
                    'exception_time' => [
                        ['time_begin' => '12:30']
                    ]
                ],
            ],
            'same_day' => 1,
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 0,
        );

        $this->dbProphecy->sql_insert_id()->shouldBeCalled();
        $this->dbProphecy->exec_DELETEquery(Argument::cetera())->shouldBeCalled();
        $this->dbProphecy->exec_UPDATEquery(Argument::cetera())->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$tuesday->format('U')),
            Argument::withEntry('day_time', (int)$tuesday->format('U')),
            Argument::withEntry('sort_day_time', (int)$tuesday->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$wednesday->format('U')),
            Argument::withEntry('day_time', (int)$wednesdayMorning->format('U')),
            Argument::withEntry('sort_day_time', (int)$wednesdayMorning->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$wednesday->format('U')),
            Argument::withEntry('day_time', (int)$wednesdayEvening->format('U')),
            Argument::withEntry('sort_day_time', (int)$wednesdayEvening->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$thursday->format('U')),
            Argument::withEntry('day_time', (int)$thursday->format('U')),
            Argument::withEntry('sort_day_time', (int)$thursday->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$friday->format('U')),
            Argument::withEntry('day_time', (int)$fridayLaunch->format('U')),
            Argument::withEntry('sort_day_time', (int)$fridayLaunch->format('U'))
        ))->shouldBeCalled();

        $this->subject->createDayRelations($event);
    }

    /**
     * Test a recurring event with multiple exception times for one special day
     * In that case day is current day at midnight
     * day_time is current day + specified time
     * sort_day_time is always current day + first time
     *
     * Activated special feature for recurring events which will start multiple time at same day
     *
     * @test
     */
    public function createDayRelationsWithRecurringEventAndMultipleExceptionTimesMerge()
    {
        $this->extConfProphecy->getMergeEvents()->willReturn(true);

        $tuesday = new \DateTime();
        $tuesday->modify('last tuesday midnight');
        $wednesday = clone $tuesday;
        $wednesday->modify('+1 day');
        $wednesdayMorning = clone $wednesday;
        $wednesdayMorning->modify('08:00');
        $wednesdayEvening = clone $wednesday;
        $wednesdayEvening->modify('20:15');
        $thursday = clone $wednesday;
        $thursday->modify('+1 day');
        $friday = clone $thursday;
        $friday->modify('+1 day');
        $fridayLaunch = clone $friday;
        $fridayLaunch->modify('12:30');

        $event = array(
            'uid' => 123,
            'pid' => 321,
            'event_type' => 'recurring',
            'event_begin' => $tuesday->format('U'),
            'recurring_end' => $thursday->format('U'),
            'exceptions' => [
                [
                    'exception_type' => 'Add',
                    'exception_date' => $wednesday->format('U'),
                    'exception_time' => [
                        ['time_begin' => '08:00']
                    ]
                ],
                [
                    'exception_type' => 'Time',
                    'exception_date' => $wednesday->format('U'),
                    'exception_time' => [
                        ['time_begin' => '20:15']
                    ]
                ],
                [
                    'exception_type' => 'Add',
                    'exception_date' => $friday->format('U'),
                    'exception_time' => [
                        ['time_begin' => '12:30']
                    ]
                ],
            ],
            'same_day' => 1,
            'xth' => 31,
            'weekday' => 127,
            'each_weeks' => 0,
        );

        $this->dbProphecy->sql_insert_id()->shouldBeCalled();
        $this->dbProphecy->exec_DELETEquery(Argument::cetera())->shouldBeCalled();
        $this->dbProphecy->exec_UPDATEquery(Argument::cetera())->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$tuesday->format('U')),
            Argument::withEntry('day_time', (int)$tuesday->format('U')),
            Argument::withEntry('sort_day_time', (int)$tuesday->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$wednesday->format('U')),
            Argument::withEntry('day_time', (int)$wednesdayMorning->format('U')),
            Argument::withEntry('sort_day_time', (int)$wednesdayMorning->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$wednesday->format('U')),
            Argument::withEntry('day_time', (int)$wednesdayEvening->format('U')),
            Argument::withEntry('sort_day_time', (int)$wednesdayMorning->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$thursday->format('U')),
            Argument::withEntry('day_time', (int)$thursday->format('U')),
            Argument::withEntry('sort_day_time', (int)$thursday->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$friday->format('U')),
            Argument::withEntry('day_time', (int)$fridayLaunch->format('U')),
            Argument::withEntry('sort_day_time', (int)$fridayLaunch->format('U'))
        ))->shouldBeCalled();

        $this->subject->createDayRelations($event);
    }

    /**
     * @test
     */
    public function createDayRelationsWithSingleEvent()
    {
        $nextWeek = new \DateTime();
        $nextWeek->modify('+1 week midnight');

        $event = array(
            'uid' => 123,
            'pid' => 321,
            'event_type' => 'single',
            'event_begin' => $nextWeek->format('U'),
            'xth' => 0,
            'weekday' => 0,
            'each_weeks' => 0,
            'exceptions' => 0,
        );

        $this->dbProphecy->sql_insert_id()->shouldBeCalled();
        $this->dbProphecy->exec_DELETEquery(Argument::cetera())->shouldBeCalled();
        $this->dbProphecy->exec_UPDATEquery(Argument::cetera())->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$nextWeek->format('U')),
            Argument::withEntry('day_time', (int)$nextWeek->format('U')),
            Argument::withEntry('sort_day_time', (int)$nextWeek->format('U'))
        ))->shouldBeCalled();

        $this->subject->createDayRelations($event);
    }

    /**
     * @test
     */
    public function createDayRelationsWithSingleEventAndTime()
    {
        $nextWeek = new \DateTime();
        $nextWeek->modify('+1 week midnight');
        $nextWeekMidnight = clone $nextWeek;
        $nextWeekMidnight->modify('23:59');

        $event = array(
            'uid' => 123,
            'pid' => 321,
            'event_type' => 'single',
            'event_begin' => $nextWeek->format('U'),
            'event_time' => [
                ['time_begin' => '23:59']
            ],
            'xth' => 0,
            'weekday' => 0,
            'each_weeks' => 0,
            'exceptions' => 0,
        );

        $this->dbProphecy->sql_insert_id()->shouldBeCalled();
        $this->dbProphecy->exec_DELETEquery(Argument::cetera())->shouldBeCalled();
        $this->dbProphecy->exec_UPDATEquery(Argument::cetera())->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$nextWeek->format('U')),
            Argument::withEntry('day_time', (int)$nextWeekMidnight->format('U')),
            Argument::withEntry('sort_day_time', (int)$nextWeekMidnight->format('U'))
        ))->shouldBeCalled();

        $this->subject->createDayRelations($event);
    }

    /**
     * @test
     */
    public function createDayRelationsWithDurationEvent()
    {
        $today = new \DateTime();
        $today->modify('midnight');
        $tomorrow = clone $today;
        $tomorrow->modify('+1 day');
        $in2days = clone $today;
        $in2days->modify('+2 days');

        $event = array(
            'uid' => 123,
            'pid' => 321,
            'event_type' => 'duration',
            'event_begin' => $today->format('U'),
            'event_end' => $in2days->format('U'),
            'xth' => 0,
            'weekday' => 0,
            'each_weeks' => 0,
            'exceptions' => 0,
        );

        $this->dbProphecy->sql_insert_id()->shouldBeCalled();
        $this->dbProphecy->exec_DELETEquery(Argument::cetera())->shouldBeCalled();
        $this->dbProphecy->exec_UPDATEquery(Argument::cetera())->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$today->format('U')),
            Argument::withEntry('day_time', (int)$today->format('U')),
            Argument::withEntry('sort_day_time', (int)$today->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$tomorrow->format('U')),
            Argument::withEntry('day_time', (int)$tomorrow->format('U')),
            Argument::withEntry('sort_day_time', (int)$today->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$in2days->format('U')),
            Argument::withEntry('day_time', (int)$in2days->format('U')),
            Argument::withEntry('sort_day_time', (int)$today->format('U'))
        ))->shouldBeCalled();

        $this->subject->createDayRelations($event);
    }

    /**
     * @test
     */
    public function createDayRelationsWithDurationEventWithTime()
    {
        $today = new \DateTime();
        $today->modify('midnight');
        $todayMorning = new \DateTime();
        $todayMorning->modify('08:12');
        $tomorrow = clone $today;
        $tomorrow->modify('+1 day');
        $tomorrowMorning = clone $todayMorning;
        $tomorrowMorning->modify('+1 day');
        $in2days = clone $today;
        $in2days->modify('+2 days');
        $in2daysMorning = clone $tomorrowMorning;
        $in2daysMorning->modify('+1 day');

        $event = array(
            'uid' => 123,
            'pid' => 321,
            'event_type' => 'duration',
            'event_begin' => $today->format('U'),
            'event_end' => $in2days->format('U'),
            'event_time' => [
                ['time_begin' => '08:12']
            ],
            'xth' => 0,
            'weekday' => 0,
            'each_weeks' => 0,
            'exceptions' => 0,
        );

        $this->dbProphecy->sql_insert_id()->shouldBeCalled();
        $this->dbProphecy->exec_DELETEquery(Argument::cetera())->shouldBeCalled();
        $this->dbProphecy->exec_UPDATEquery(Argument::cetera())->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$today->format('U')),
            Argument::withEntry('day_time', (int)$todayMorning->format('U')),
            Argument::withEntry('sort_day_time', (int)$todayMorning->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$tomorrow->format('U')),
            Argument::withEntry('day_time', (int)$tomorrowMorning->format('U')),
            Argument::withEntry('sort_day_time', (int)$todayMorning->format('U'))
        ))->shouldBeCalled();
        $this->dbProphecy->exec_INSERTquery('tx_events2_domain_model_day', Argument::allOf(
            Argument::withEntry('day', (int)$in2days->format('U')),
            Argument::withEntry('day_time', (int)$in2daysMorning->format('U')),
            Argument::withEntry('sort_day_time', (int)$todayMorning->format('U'))
        ))->shouldBeCalled();

        $this->subject->createDayRelations($event);
    }
}
