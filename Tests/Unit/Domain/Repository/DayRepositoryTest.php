<?php

namespace JWeiland\Events2\Tests\Unit\Domain\Repository;

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
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Repository\DayRepository;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;

/**
 * Test case.
 *
 * @author Stefan Froemken <projects@jweiland.net>
 */
class DayRepositoryTest extends UnitTestCase
{
    /**
     * @var \JWeiland\Events2\Domain\Repository\DayRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject;

    /**
     * set up.
     */
    public function setUp()
    {
        $this->subject = $this->getMock(DayRepository::class, array('createQuery'), array(), '', false);
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
    public function getNextDayForEventDoesNotRespectStoragePage()
    {
        /** @var Typo3QuerySettings|\PHPUnit_Framework_MockObject_MockObject $querySettings */
        $querySettings = $this->getMock(Typo3QuerySettings::class, array('setRespectStoragePage'), array(), '', false);
        $querySettings->expects($this->once())->method('setRespectStoragePage')->with($this->identicalTo(false));

        /** @var QueryResult|\PHPUnit_Framework_MockObject_MockObject $queryResult */
        $queryResult = $this->getMock(QueryResult::class, array(), array(), '', false);

        /** @var Query|\PHPUnit_Framework_MockObject_MockObject $query */
        $query = $this->getMock(Query::class, array('getQuerySettings', 'matching', 'equals', 'greaterThanOrEqual', 'logicalAnd', 'execute'), array(), '', false);
        $query->expects($this->once())->method('getQuerySettings')->willReturn($querySettings);
        $query->expects($this->once())->method('matching')->willReturn($query);
        $query->expects($this->once())->method('execute')->willReturn($queryResult);

        $this->subject->expects($this->once())->method('createQuery')->willReturn($query);

        $this->subject->getNextDayForEvent(new Event());
    }

    /**
     * @test
     */
    public function getLastDayForEventDoesNotRespectStoragePage()
    {
        /** @var Typo3QuerySettings|\PHPUnit_Framework_MockObject_MockObject $querySettings */
        $querySettings = $this->getMock(Typo3QuerySettings::class, array('setRespectStoragePage'), array(), '', false);
        $querySettings->expects($this->once())->method('setRespectStoragePage')->with($this->identicalTo(false));

        /** @var QueryResult|\PHPUnit_Framework_MockObject_MockObject $queryResult */
        $queryResult = $this->getMock(QueryResult::class, array(), array(), '', false);

        /** @var Query|\PHPUnit_Framework_MockObject_MockObject $query */
        $query = $this->getMock(Query::class, array('getQuerySettings', 'matching', 'equals', 'execute'), array(), '', false);
        $query->expects($this->once())->method('getQuerySettings')->willReturn($querySettings);
        $query->expects($this->once())->method('matching')->willReturn($query);
        $query->expects($this->once())->method('execute')->willReturn($queryResult);

        $this->subject->expects($this->once())->method('createQuery')->willReturn($query);

        $this->subject->getLastDayForEvent(new Event());
    }
}
