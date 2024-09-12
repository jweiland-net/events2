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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for UserSession
 */
class UserSessionTest extends FunctionalTestCase
{
    protected UserSession $subject;

    protected array $testExtensionsToLoad = [
        'jweiland/events2',
    ];

    protected function setUp(): void
    {
        self::markTestIncomplete('UserSessionTest not updated until right now');

        parent::setUp();

        $feUser = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
        $feUser->initializeUserSessionManager();

        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->fe_user = $feUser;

        $this->subject = new UserSession();
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
            $GLOBALS['TSFE'],
        );

        parent::tearDown();
    }

    /**
     * @test
     */
    public function getMonthAndYearWillReturnEmptyArray(): void
    {
        self::assertEmpty(
            $this->subject->getMonthAndYear(),
        );
    }

    /**
     * DataProvider for year and month.
     *
     * @return array<string, array<int|string>>
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
    public function getMonthAndYearWillReturnMonthAndYear(int $month, int $year, string $expectedMonth, string $expectedYear): void
    {
        $this->subject->setMonthAndYear($month, $year);
        self::assertSame(
            [
                'month' => $expectedMonth,
                'year' => $expectedYear,
            ],
            $this->subject->getMonthAndYear(),
        );
    }
}
