<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Domain\Repository;

use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Repository\CategoryRepository;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;

/**
 * Test case.
 */
class CategoryRepositoryTest extends UnitTestCase
{
    /**
     * @var \JWeiland\Events2\Domain\Repository\CategoryRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject;

    public function setUp()
    {
        $this->subject = $this
            ->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['createQuery'])
            ->getMock();
    }

    public function tearDown()
    {
        unset($this->subject);
    }

    /**
     * @test
     */
    public function getSelectedCategoriesConvertsWrongCategoriesToInteger()
    {
        /** @var QuerySettingsInterface|\PHPUnit_Framework_MockObject_MockObject $querySettings */
        $querySettings = $this
            ->getMockBuilder(Typo3QuerySettings::class)
            ->getMock();
        $querySettings->expects(self::once())->method('setRespectSysLanguage')->with(
            self::equalTo(false)
        );

        /** @var Query|\PHPUnit_Framework_MockObject_MockObject $query */
        $query = $this
            ->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->getMock();
        $query->expects(self::once())->method('getQuerySettings')->willReturn($querySettings);
        $query->expects(self::once())->method('matching')->willReturn($query);
        $query->expects(self::once())->method('in')->with(
            self::equalTo('uid'),
            self::equalTo([1, 2, 4])
        );
        $query->expects(self::once())->method('execute')->willReturn(
            new QueryResult(new Query(Event::class))
        );

        $this->subject->expects(self::once())->method('createQuery')->willReturn($query);

        $this->subject->getSelectedCategories('1,2test,drei,4');
    }

    /**
     * @test
     */
    public function getSelectedCategoriesWithGivenParentWillCallEquals()
    {
        /** @var QuerySettingsInterface|\PHPUnit_Framework_MockObject_MockObject $querySettings */
        $querySettings = $this
            ->getMockBuilder(Typo3QuerySettings::class)
            ->getMock();
        $querySettings->expects(self::once())->method('setRespectSysLanguage')->with(
            self::equalTo(false)
        );

        /** @var Query|\PHPUnit_Framework_MockObject_MockObject $query */
        $query = $this
            ->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->getMock();
        $query->expects(self::once())->method('getQuerySettings')->willReturn($querySettings);
        $query->expects(self::once())->method('equals')->with(
            self::equalTo('parent'),
            self::equalTo(5)
        );
        $query->expects(self::once())->method('matching')->willReturn($query);
        $query->expects(self::once())->method('execute')->willReturn(
            new QueryResult(new Query(Event::class))
        );

        $this->subject->expects(self::once())->method('createQuery')->willReturn($query);

        // parent (5) should be casted to integer
        $this->subject->getSelectedCategories('1,2,3,4', 5);
    }
}
