<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Session;

use JWeiland\Events2\Session\UserSession;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

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

    public function setUp(): void
    {
        parent::setUp();

        $feUser = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
        $this->subject = new UserSession($feUser);
    }

    public function tearDown(): void
    {
        unset(
            $this->subject
        );

        parent::tearDown();
    }

    /**
     * @test
     */
    public function getMonthAndYearWillReturnEmptyArray(): void
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
    public function yearAndMonthDataProvider(): array
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
    public function getMonthAndYearWillReturnMonthAndYear($month, $year, $expectedMonth, $expectedYear): void
    {
        $this->subject->setMonthAndYear($month, $year);
        self::assertSame(
            [
                'month' => $expectedMonth,
                'year' => $expectedYear
            ],
            $this->subject->getMonthAndYear()
        );
    }
}
