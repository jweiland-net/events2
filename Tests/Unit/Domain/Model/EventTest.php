<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Domain\Model;

use JWeiland\Events2\Domain\Model\Category;
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Exception;
use JWeiland\Events2\Domain\Model\Link;
use JWeiland\Events2\Domain\Model\Location;
use JWeiland\Events2\Domain\Model\Organizer;
use JWeiland\Events2\Domain\Model\Time;
use JWeiland\Events2\Tests\Unit\Domain\Traits\TestTypo3PropertiesTrait;
use Nimut\TestingFramework\MockObject\AccessibleMockObjectInterface;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Test case.
 */
class EventTest extends UnitTestCase
{
    use TestTypo3PropertiesTrait;

    /**
     * @var \JWeiland\Events2\Domain\Model\Event
     */
    protected $subject;

    /**
     * set up.
     */
    public function setUp()
    {
        $this->subject = new Event();
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
    public function getTitleInitiallyReturnsEmptyString()
    {
        self::assertSame(
            '',
            $this->subject->getTitle()
        );
    }

    /**
     * @test
     */
    public function setTitleSetsTitle()
    {
        $this->subject->setTitle('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getTitle()
        );
    }

    /**
     * @test
     */
    public function setTitleWithIntegerResultsInString()
    {
        $this->subject->setTitle(123);
        self::assertSame('123', $this->subject->getTitle());
    }

    /**
     * @test
     */
    public function setTitleWithBooleanResultsInString()
    {
        $this->subject->setTitle(true);
        self::assertSame('1', $this->subject->getTitle());
    }

    /**
     * @test
     */
    public function getTopOfListInitiallyReturnsFalse()
    {
        self::assertSame(
            false,
            $this->subject->getTopOfList()
        );
    }

    /**
     * @test
     */
    public function setTopOfListSetsTopOfList()
    {
        $this->subject->setTopOfList(true);
        self::assertSame(
            true,
            $this->subject->getTopOfList()
        );
    }

    /**
     * @test
     */
    public function setTopOfListWithStringReturnsTrue()
    {
        $this->subject->setTopOfList('foo bar');
        self::assertTrue($this->subject->getTopOfList());
    }

    /**
     * @test
     */
    public function setTopOfListWithZeroReturnsFalse()
    {
        $this->subject->setTopOfList(0);
        self::assertFalse($this->subject->getTopOfList());
    }

    /**
     * @test
     */
    public function getTeaserInitiallyReturnsEmptyString()
    {
        self::assertSame(
            '',
            $this->subject->getTeaser()
        );
    }

    /**
     * @test
     */
    public function setTeaserSetsTeaser()
    {
        $this->subject->setTeaser('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getTeaser()
        );
    }

    /**
     * @test
     */
    public function setTeaserWithIntegerResultsInString()
    {
        $this->subject->setTeaser(123);
        self::assertSame('123', $this->subject->getTeaser());
    }

    /**
     * @test
     */
    public function setTeaserWithBooleanResultsInString()
    {
        $this->subject->setTeaser(true);
        self::assertSame('1', $this->subject->getTeaser());
    }

    /**
     * @test
     */
    public function getEventBeginInitiallyReturnsNull()
    {
        self::assertNull(
            $this->subject->getEventBegin()
        );
    }

    /**
     * @test
     */
    public function setEventBeginSetsEventBegin()
    {
        $date = new \DateTime();
        $this->subject->setEventBegin($date);

        self::assertEquals(
            $date,
            $this->subject->getEventBegin()
        );
    }

    /**
     * @test
     */
    public function getEventTimeInitiallyReturnsNull()
    {
        self::assertNull($this->subject->getEventTime());
    }

    /**
     * @test
     */
    public function getEventTimeWithoutAnyTimesInAnyRelationsResultsInTimeOfCurrentEvent()
    {
        $time = new Time();
        $time->setTimeBegin('09:34');
        $this->subject->setEventTime($time);
        self::assertEquals(
            $time,
            $this->subject->getEventTime()
        );
    }

    /**
     * @test
     */
    public function setEventTimeSetsEventTime()
    {
        $instance = new Time();
        $this->subject->setEventTime($instance);

        self::assertSame(
            $instance,
            $this->subject->getEventTime()
        );
    }

    /**
     * @test
     */
    public function getDaysOfEventsTakingDaysWithEqualDaysReturnsZero()
    {
        $eventBegin = new \DateTime('midnight');
        $eventEnd = new \DateTime('midnight');
        $eventEnd->modify('+20 seconds');
        $this->subject->setEventBegin($eventBegin);
        $this->subject->setEventEnd($eventEnd);

        self::assertSame(
            0,
            $this->subject->getDaysOfEventsTakingDays()
        );
    }

    /**
     * @test
     */
    public function getDaysOfEventsTakingDaysWithNoneEventEndResultsInZero()
    {
        $eventBegin = new \DateTime();
        $this->subject->setEventBegin($eventBegin);

        self::assertSame(
            0,
            $this->subject->getDaysOfEventsTakingDays()
        );
    }

    /**
     * @test
     */
    public function getDaysOfEventsTakingDaysWithDifferentDatesResultsInFourDays()
    {
        $eventBegin = new \DateTime();
        $eventEnd = new \DateTime(); // f.e. monday
        $eventEnd->modify('+4 days'); // mo + 4 = 5 days: mo->tu->we->th->fr
        $this->subject->setEventBegin($eventBegin);
        $this->subject->setEventEnd($eventEnd);

        self::assertSame(
            5,
            $this->subject->getDaysOfEventsTakingDays()
        );
    }

    /**
     * @test
     */
    public function getEventEndInitiallyReturnsNull()
    {
        self::assertNull($this->subject->getEventEnd());
    }

    /**
     * @test
     */
    public function setEventEndSetsEventEnd()
    {
        $instance = new \DateTime();
        $this->subject->setEventEnd($instance);

        self::assertEquals(
            $instance,
            $this->subject->getEventEnd()
        );
    }

    /**
     * @test
     */
    public function getSameDayInitiallyReturnsFalse()
    {
        self::assertSame(
            false,
            $this->subject->getSameDay()
        );
    }

    /**
     * @test
     */
    public function setSameDaySetsSameDay()
    {
        $this->subject->setSameDay(true);
        self::assertSame(
            true,
            $this->subject->getSameDay()
        );
    }

    /**
     * @test
     */
    public function setSameDayWithStringReturnsTrue()
    {
        $this->subject->setSameDay('foo bar');
        self::assertTrue($this->subject->getSameDay());
    }

    /**
     * @test
     */
    public function setSameDayWithZeroReturnsFalse()
    {
        $this->subject->setSameDay(0);
        self::assertFalse($this->subject->getSameDay());
    }

    /**
     * @test
     */
    public function getMultipleTimesInitiallyReturnsObjectStorage()
    {
        self::assertEquals(
            new ObjectStorage(),
            $this->subject->getMultipleTimes()
        );
    }

    /**
     * @test
     */
    public function setMultipleTimesSetsMultipleTimes()
    {
        $instance = new ObjectStorage();
        $this->subject->setMultipleTimes($instance);

        self::assertSame(
            $instance,
            $this->subject->getMultipleTimes()
        );
    }

    /**
     * @test
     */
    public function getXthInitiallyResultsInArrayWhereAllValuesAreZero()
    {
        $GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['xth']['config']['items'] = [
            ['first', 'first'],
            ['second', 'second'],
            ['third', 'third'],
            ['fourth', 'fourth'],
            ['fifth', 'fifth'],
        ];

        $expectedArray = [
            'first' => 0,
            'second' => 0,
            'third' => 0,
            'fourth' => 0,
            'fifth' => 0,
        ];

        self::assertSame(
            $expectedArray,
            $this->subject->getXth()
        );
    }

    /**
     * @test
     */
    public function setXthWithZwentyThreeResultsInArrayWithDifferentValues()
    {
        $GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['xth']['config']['items'] = [
            ['first', 'first'],
            ['second', 'second'],
            ['third', 'third'],
            ['fourth', 'fourth'],
            ['fifth', 'fifth'],
        ];

        $expectedArray = [
            'first' => 1,
            'second' => 2,
            'third' => 4,
            'fourth' => 0,
            'fifth' => 16,
        ];
        $this->subject->setXth(23);

        self::assertSame(
            $expectedArray,
            $this->subject->getXth()
        );
    }

    /**
     * @test
     */
    public function getWeekdayInitiallyResultsInArrayWhereAllValuesAreZero()
    {
        $GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['weekday']['config']['items'] = [
            ['monday', 'monday'],
            ['tuesday', 'tuesday'],
            ['wednesday', 'wednesday'],
            ['thursday', 'thursday'],
            ['friday', 'friday'],
            ['saturday', 'saturday'],
            ['sunday', 'sunday'],
        ];

        $expectedArray = [
            'monday' => 0,
            'tuesday' => 0,
            'wednesday' => 0,
            'thursday' => 0,
            'friday' => 0,
            'saturday' => 0,
            'sunday' => 0,
        ];

        self::assertSame(
            $expectedArray,
            $this->subject->getWeekday()
        );
    }

    /**
     * @test
     */
    public function setWeekdayWithEightySevenResultsInArrayWithDifferentValues()
    {
        $GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['weekday']['config']['items'] = [
            ['monday', 'monday'],
            ['tuesday', 'tuesday'],
            ['wednesday', 'wednesday'],
            ['thursday', 'thursday'],
            ['friday', 'friday'],
            ['saturday', 'saturday'],
            ['sunday', 'sunday'],
        ];

        $expectedArray = [
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 4,
            'thursday' => 0,
            'friday' => 16,
            'saturday' => 0,
            'sunday' => 64,
        ];
        $this->subject->setWeekday(87);

        self::assertSame(
            $expectedArray,
            $this->subject->getWeekday()
        );
    }

    /**
     * @test
     */
    public function getDifferentTimesInitiallyReturnsObjectStorage()
    {
        self::assertEquals(
            new ObjectStorage(),
            $this->subject->getDifferentTimes()
        );
    }

    /**
     * @test
     */
    public function setDifferentTimesSetsDifferentTimes()
    {
        $object = new Time();
        $objectStorage = new ObjectStorage();
        $objectStorage->attach($object);
        $this->subject->setDifferentTimes($objectStorage);

        self::assertSame(
            $objectStorage,
            $this->subject->getDifferentTimes()
        );
    }

    /**
     * @test
     */
    public function addDifferentTimeAddsOneDifferentTime()
    {
        $objectStorage = new ObjectStorage();
        $this->subject->setDifferentTimes($objectStorage);

        $object = new Time();
        $this->subject->addDifferentTime($object);

        $objectStorage->attach($object);

        self::assertSame(
            $objectStorage,
            $this->subject->getDifferentTimes()
        );
    }

    /**
     * @test
     */
    public function removeDifferentTimeRemovesOneDifferentTime()
    {
        $object = new Time();
        $objectStorage = new ObjectStorage();
        $objectStorage->attach($object);
        $this->subject->setDifferentTimes($objectStorage);

        $this->subject->removeDifferentTime($object);
        $objectStorage->detach($object);

        self::assertSame(
            $objectStorage,
            $this->subject->getDifferentTimes()
        );
    }

    /**
     * @test
     */
    public function getEachWeeksInitiallyReturnsZero()
    {
        self::assertSame(
            0,
            $this->subject->getEachWeeks()
        );
    }

    /**
     * @test
     */
    public function setEachWeeksSetsEachWeeks()
    {
        $this->subject->setEachWeeks(123456);

        self::assertSame(
            123456,
            $this->subject->getEachWeeks()
        );
    }

    /**
     * @test
     */
    public function setEachWeeksWithStringResultsInInteger()
    {
        $this->subject->setEachWeeks('123Test');

        self::assertSame(
            123,
            $this->subject->getEachWeeks()
        );
    }

    /**
     * @test
     */
    public function setEachWeeksWithBooleanResultsInInteger()
    {
        $this->subject->setEachWeeks(true);

        self::assertSame(
            1,
            $this->subject->getEachWeeks()
        );
    }

    /**
     * @test
     */
    public function getEachMonthsInitiallyReturnsZero()
    {
        self::assertSame(
            0,
            $this->subject->getEachMonths()
        );
    }

    /**
     * @test
     */
    public function setEachMonthsSetsEachMonths()
    {
        $this->subject->setEachMonths(123456);

        self::assertSame(
            123456,
            $this->subject->getEachMonths()
        );
    }

    /**
     * @test
     */
    public function setEachMonthsWithStringResultsInInteger()
    {
        $this->subject->setEachMonths('123Test');

        self::assertSame(
            123,
            $this->subject->getEachMonths()
        );
    }

    /**
     * @test
     */
    public function setEachMonthsWithBooleanResultsInInteger()
    {
        $this->subject->setEachMonths(true);

        self::assertSame(
            1,
            $this->subject->getEachMonths()
        );
    }

    /**
     * @test
     */
    public function getRecurringEndInitiallyReturnsNull()
    {
        self::assertNull(
            $this->subject->getRecurringEnd()
        );
    }

    /**
     * @test
     */
    public function setRecurringEndSetsRecurringEnd()
    {
        $date = new \DateTime();
        $this->subject->setRecurringEnd($date);

        self::assertEquals(
            $date,
            $this->subject->getRecurringEnd()
        );
    }

    /**
     * @test
     */
    public function getExceptionsInitiallyReturnsObjectStorage()
    {
        self::assertEquals(
            new ObjectStorage(),
            $this->subject->getExceptions()
        );
    }

    /**
     * @test
     */
    public function setExceptionsSetsExceptions()
    {
        $object = new Exception();
        $objectStorage = new ObjectStorage();
        $objectStorage->attach($object);
        $this->subject->setExceptions($objectStorage);

        self::assertSame(
            $objectStorage,
            $this->subject->getExceptions()
        );
    }

    /**
     * @test
     */
    public function addExceptionAddsOneDifferentTime()
    {
        $objectStorage = new ObjectStorage();
        $this->subject->setExceptions($objectStorage);

        $object = new Exception();
        $this->subject->addException($object);

        $objectStorage->attach($object);

        self::assertSame(
            $objectStorage,
            $this->subject->getExceptions()
        );
    }

    /**
     * @test
     */
    public function removeExceptionRemovesOneException()
    {
        $object = new Exception();
        $objectStorage = new ObjectStorage();
        $objectStorage->attach($object);
        $this->subject->setExceptions($objectStorage);

        $this->subject->removeException($object);
        $objectStorage->detach($object);

        self::assertSame(
            $objectStorage,
            $this->subject->getExceptions()
        );
    }

    /**
     *
     */
    public function getFutureExeptions()
    {
        // skip
    }

    /**
     * @test
     */
    public function getDetailInformationsInitiallyReturnsEmptyString()
    {
        self::assertSame(
            '',
            $this->subject->getDetailInformations()
        );
    }

    /**
     * @test
     */
    public function setDetailInformationsSetsDetailInformations()
    {
        $this->subject->setDetailInformations('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getDetailInformations()
        );
    }

    /**
     * @test
     */
    public function setDetailInformationsWithIntegerResultsInString()
    {
        $this->subject->setDetailInformations(123);
        self::assertSame('123', $this->subject->getDetailInformations());
    }

    /**
     * @test
     */
    public function setDetailInformationsWithBooleanResultsInString()
    {
        $this->subject->setDetailInformations(true);
        self::assertSame('1', $this->subject->getDetailInformations());
    }

    /**
     * @test
     */
    public function getFreeEntryInitiallyReturnsFalse()
    {
        self::assertSame(
            false,
            $this->subject->getFreeEntry()
        );
    }

    /**
     * @test
     */
    public function setFreeEntrySetsFreeEntry()
    {
        $this->subject->setFreeEntry(true);
        self::assertSame(
            true,
            $this->subject->getFreeEntry()
        );
    }

    /**
     * @test
     */
    public function setFreeEntryWithStringReturnsTrue()
    {
        $this->subject->setFreeEntry('foo bar');
        self::assertTrue($this->subject->getFreeEntry());
    }

    /**
     * @test
     */
    public function setFreeEntryWithZeroReturnsFalse()
    {
        $this->subject->setFreeEntry(0);
        self::assertFalse($this->subject->getFreeEntry());
    }

    /**
     * @test
     */
    public function getTicketLinkInitiallyReturnsNull()
    {
        self::assertNull($this->subject->getTicketLink());
    }

    /**
     * @test
     */
    public function setTicketLinkSetsTicketLink()
    {
        $instance = new Link();
        $this->subject->setTicketLink($instance);

        self::assertSame(
            $instance,
            $this->subject->getTicketLink()
        );
    }

    /**
     * @test
     */
    public function getCategoriesInitiallyReturnsObjectStorage()
    {
        self::assertEquals(
            new ObjectStorage(),
            $this->subject->getCategories()
        );
    }

    /**
     * @test
     */
    public function setCategoriesSetsCategories()
    {
        $object = new Category();
        $objectStorage = new ObjectStorage();
        $objectStorage->attach($object);
        $this->subject->setCategories($objectStorage);

        self::assertSame(
            $objectStorage,
            $this->subject->getCategories()
        );
    }

    /**
     * @test
     */
    public function addCategoryAddsOneCategory()
    {
        $objectStorage = new ObjectStorage();
        $this->subject->setCategories($objectStorage);

        $object = new Category();
        $this->subject->addCategory($object);

        $objectStorage->attach($object);

        self::assertSame(
            $objectStorage,
            $this->subject->getCategories()
        );
    }

    /**
     * @test
     */
    public function removeCategoryRemovesOneCategory()
    {
        $object = new Category();
        $objectStorage = new ObjectStorage();
        $objectStorage->attach($object);
        $this->subject->setCategories($objectStorage);

        $this->subject->removeCategory($object);
        $objectStorage->detach($object);

        self::assertSame(
            $objectStorage,
            $this->subject->getCategories()
        );
    }

    /**
     * @test
     */
    public function getCategoryListReturnsCommaSeparatedList()
    {
        for ($i = 1; $i < 4; $i++) {
            /* @var Category|\PHPUnit_Framework_MockObject_MockObject|AccessibleMockObjectInterface $category */
            $category = $this->getAccessibleMock(Category::class, ['dummy']);
            $category->_set('uid', $i);
            $this->subject->addCategory($category);
        }
        self::assertSame(
            [1, 2, 3],
            $this->subject->getCategoryUids()
        );
    }

    /**
     * @test
     */
    public function getDaysInitiallyReturnsObjectStorage()
    {
        self::assertEquals(
            new ObjectStorage(),
            $this->subject->getDays()
        );
    }

    /**
     * @test
     */
    public function setDaysSetsDays()
    {
        $object = new Day();
        $objectStorage = new ObjectStorage();
        $objectStorage->attach($object);
        $this->subject->setDays($objectStorage);

        self::assertSame(
            $objectStorage,
            $this->subject->getDays()
        );
    }

    /**
     * @test
     */
    public function addDayAddsOneDifferentTime()
    {
        $objectStorage = new ObjectStorage();
        $this->subject->setDays($objectStorage);

        $object = new Day();
        $this->subject->addDay($object);

        $objectStorage->attach($object);

        self::assertSame(
            $objectStorage,
            $this->subject->getDays()
        );
    }

    /**
     * @test
     */
    public function removeDayRemovesOneDay()
    {
        $object = new Day();
        $objectStorage = new ObjectStorage();
        $objectStorage->attach($object);
        $this->subject->setDays($objectStorage);

        $this->subject->removeDay($object);
        $objectStorage->detach($object);

        self::assertSame(
            $objectStorage,
            $this->subject->getDays()
        );
    }

    /**
     * @test
     */
    public function getLocationInitiallyReturnsNull()
    {
        self::assertNull($this->subject->getLocation());
    }

    /**
     * @test
     */
    public function setLocationSetsLocation()
    {
        $instance = new Location();
        $this->subject->setLocation($instance);

        self::assertSame(
            $instance,
            $this->subject->getLocation()
        );
    }

    /**
     * @test
     */
    public function getOrganizerInitiallyReturnsNull()
    {
        self::assertNull($this->subject->getOrganizer());
    }

    /**
     * @test
     */
    public function setOrganizerSetsOrganizer()
    {
        $instance = new Organizer();
        $this->subject->setOrganizer($instance);

        self::assertSame(
            $instance,
            $this->subject->getOrganizer()
        );
    }

    /**
     * @test
     */
    public function getImagesInitiallyReturnsArray()
    {
        self::assertEquals(
            [],
            $this->subject->getImages()
        );
    }

    /**
     * @test
     */
    public function setImagesSetsImages()
    {
        $object = new Time();
        $objectStorage = new ObjectStorage();
        $objectStorage->attach($object);
        $this->subject->setImages($objectStorage);

        self::assertSame(
            [0 => $object],
            $this->subject->getImages()
        );
    }

    /**
     * @test
     */
    public function getVideoLinkInitiallyReturnsNull()
    {
        self::assertNull($this->subject->getVideoLink());
    }

    /**
     * @test
     */
    public function setVideoLinkSetsVideoLink()
    {
        $instance = new Link();
        $this->subject->setVideoLink($instance);

        self::assertSame(
            $instance,
            $this->subject->getVideoLink()
        );
    }

    /**
     * @test
     */
    public function getDownloadLinksInitiallyReturnsObjectStorage()
    {
        self::assertEquals(
            new ObjectStorage(),
            $this->subject->getDownloadLinks()
        );
    }

    /**
     * @test
     */
    public function setDownloadLinksSetsDownloadLinks()
    {
        $object = new Link();
        $objectStorage = new ObjectStorage();
        $objectStorage->attach($object);
        $this->subject->setDownloadLinks($objectStorage);

        self::assertSame(
            $objectStorage,
            $this->subject->getDownloadLinks()
        );
    }

    /**
     * @test
     */
    public function addDownloadLinkAddsOneDownloadLink()
    {
        $objectStorage = new ObjectStorage();
        $this->subject->setDownloadLinks($objectStorage);

        $object = new Link();
        $this->subject->addDownloadLink($object);

        $objectStorage->attach($object);

        self::assertSame(
            $objectStorage,
            $this->subject->getDownloadLinks()
        );
    }

    /**
     * @test
     */
    public function removeDownloadLinkRemovesOneDownloadLink()
    {
        $object = new Link();
        $objectStorage = new ObjectStorage();
        $objectStorage->attach($object);
        $this->subject->setDownloadLinks($objectStorage);

        $this->subject->removeDownloadLink($object);
        $objectStorage->detach($object);

        self::assertSame(
            $objectStorage,
            $this->subject->getDownloadLinks()
        );
    }
}
