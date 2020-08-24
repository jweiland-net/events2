<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Session;

use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Link;
use JWeiland\Events2\Domain\Model\Location;
use JWeiland\Events2\Domain\Model\Organizer;
use JWeiland\Events2\Domain\Model\Time;
use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Service\DayRelationService;
use JWeiland\Events2\Service\JsonLdService;
use JWeiland\Events2\Session\UserSession;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;

/**
 * Functional test for UserSession
 */
class UserSessionTest extends FunctionalTestCase
{
    /**
     * @var UserSession
     */
    protected $subject;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/events2'
    ];

    public function setUp()
    {
        parent::setUp();

        $this->subject = new UserSession();
    }

    public function tearDown()
    {
        unset(
            $this->subject
        );

        parent::tearDown();
    }

    /**
     * @test
     */
    public function getMonthAndYearWillReturnEmptyArray()
    {
        self::assertEmpty(
            $this->subject->getMonthAndYear()
        );
    }

    /**
     * DataProvider for year and month.
     *
     * @return array
     */
    public function yearAndMonthDataProvider()
    {
        return [
            'empty month and year' => [0, 0, '01', '1970'],
            'low one digit month and year' => [1, 1, '01', '1970'],
            'high one digit month and year' => [9, 9, '09', '1970'],
            'low two digit month and year' => [10, 10, '10', '1970'],
            'high two digit month and year' => [99, 99, '12', '1970'],
            'high month and year' => [99, 9999, '12', '9999'],
        ];
    }

    /**
     * @test
     *
     * @dataProvider yearAndMonthDataProvider
     */
    public function getMonthAndYearWillReturnMonthAndYear($month, $year, $expectedMonth, $expectedYear)
    {
        $this->subject->setMonthAndYear($month,$year);
        self::assertSame(
            [
                'month' => $expectedMonth,
                'year' => $expectedYear
            ],
            $this->subject->getMonthAndYear()
        );
    }
}
