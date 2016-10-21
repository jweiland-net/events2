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
use JWeiland\Events2\Domain\Model\Filter;
use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Utility\DateTimeUtility;
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
     * @var DayRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject;
    
    /**
     * @var DateTimeUtility|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dateTimeUtility;
    
    /**
     * @var Query|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $query;
    
    /**
     * set up.
     */
    public function setUp()
    {
        $this->dateTimeUtility = new DateTimeUtility();
        $this->query = $this->getMock(Query::class, array(), array(), '', false);

        $this->subject = $this->getMock(DayRepository::class, array('createQuery'), array(), '', false);
        $this->subject
            ->expects($this->once())
            ->method('createQuery')
            ->willReturn($this->query);
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
    public function findEventsWithEmptyParametersReturnsFutureEvents()
    {
        $this->query
            ->expects($this->once())
            ->method('logicalAnd')
            ->willReturn(array());
    
        $this->query
            ->expects($this->once())
            ->method('matching')
            ->with($this->equalTo(array()))
            ->willReturn($this->query);
    
        $this->subject->injectDateTimeUtility($this->dateTimeUtility);
        
        $this->subject->findEvents('', new Filter());
    }
}
