<?php

namespace JWeiland\Events2\Tests\Unit\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Stefan Froemken <projects@jweiland.net>, jweiland.net
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use JWeiland\Events2\Domain\Model\Category;
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Test case.
 *
 * @author Stefan Froemken <projects@jweiland.net>
 */
class DayTest extends UnitTestCase
{
    /**
     * @var \JWeiland\Events2\Domain\Model\Day
     */
    protected $subject;

    /**
     * set up.
     */
    public function setUp()
    {
        $this->subject = new Day();
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
    public function getDayInitiallyReturnsNull()
    {
        $this->assertNull(
            $this->subject->getDay()
        );
    }

    /**
     * @test
     */
    public function setDaySetsDay()
    {
        $date = new \DateTime();
        $this->subject->setDay($date);

        $this->assertSame(
            $date,
            $this->subject->getDay()
        );
    }

    /**
     * @return array
     */
    public function dataProviderForSetDay()
    {
        $arguments = array();
        $arguments['set Day with Null'] = array(null);
        $arguments['set Day with Integer'] = array(1234567890);
        $arguments['set Day with Integer as String'] = array('1234567890');
        $arguments['set Day with String'] = array('Hi all together');

        return $arguments;
    }

    /**
     * @test
     *
     * @param mixed $argument
     * @dataProvider dataProviderForSetDay
     * @expectedException \PHPUnit_Framework_Error
     */
    public function setDayWithInvalidValuesResultsInException($argument)
    {
        $this->subject->setDay($argument);
    }

    /**
     * @test
     */
    public function getEventsInitiallyReturnsObjectStorage()
    {
        $this->assertEquals(
            new ObjectStorage(),
            $this->subject->getEvents()
        );
    }

    /**
     * @test
     */
    public function getEventsWithSpecifiedCategoriesReturnsReducedResult()
    {
        /* @var Category|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $category */
        $category = $this->getAccessibleMock(Category::class, array('dummy'));
        $category->_set('uid', 1);
        $eventWithCategory = new Event();
        $eventWithCategory->addCategory($category);
        $eventWithoutCategory = new Event();
        $this->subject->addEvent($eventWithCategory);
        $this->subject->addEvent($eventWithoutCategory);

        $expectedResult = new ObjectStorage();
        $expectedResult->attach($eventWithCategory);

        $this->assertEquals(
            $expectedResult,
            $this->subject->getEvents(array(1))
        );
    }

    /**
     * @test
     */
    public function getEventsWithSpecifiedStoragePidsReturnsReducedResult()
    {
        for ($i = 1; $i < 4; $i++) {
            $event = new Event();
            $event->setPid($i);
            $this->subject->addEvent($event);
        }

        $expectedResult = new ObjectStorage();
        $expectedResult->attach($event);

        $this->assertEquals(
            $expectedResult,
            $this->subject->getEvents(array(), array(3))
        );
    }

    /**
     * @test
     */
    public function setEventsSetsEvents()
    {
        $object = new Event();
        $objectStorage = new ObjectStorage();
        $objectStorage->attach($object);
        $this->subject->setEvents($objectStorage);

        $this->assertEquals(
            $objectStorage,
            $this->subject->getEvents()
        );
    }

    /**
     * @test
     */
    public function addEventAddsOneEvent()
    {
        $event = new Event();
        $this->subject->addEvent($event);

        $objectStorage = new ObjectStorage();
        $objectStorage->attach($event);

        $this->assertEquals(
            $objectStorage,
            $this->subject->getEvents()
        );
    }

    /**
     * @test
     */
    public function removeEventRemovesOneEvent()
    {
        $event = new Event();
        $objectStorage = new ObjectStorage();
        $objectStorage->attach($event);

        $this->subject->setEvents($objectStorage);

        $this->subject->removeEvent($event);

        $this->assertEquals(
            new ObjectStorage(),
            $this->subject->getEvents()
        );
    }

    /**
     * @test
     */
    public function getEventInitiallyReturnsNull()
    {
        $this->assertNull($this->subject->getEvent());
    }

    /**
     * @test
     */
    public function setEventSetsEvent()
    {
        $instance = new Event();
        $this->subject->setEvent($instance);

        $this->assertSame(
            $instance,
            $this->subject->getEvent()
        );
    }
}
