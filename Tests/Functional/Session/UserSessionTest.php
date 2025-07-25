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
use JWeiland\Events2\Tests\Functional\Events2Constants;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for UserSession
 */
class UserSessionTest extends FunctionalTestCase
{
    protected UserSession $subject;

    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'sjbr/static-info-tables',
        'jweiland/events2',
    ];

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'phpTimeZone' => Events2Constants::PHP_TIMEZONE,
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $frontendUserAuthentication = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
        $frontendUserAuthentication->initializeUserSessionManager();

        $request = new ServerRequest('https://www.example.com/', 'GET');
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $request = $request->withAttribute('frontend.user', $frontendUserAuthentication);

        $GLOBALS['TYPO3_REQUEST'] = $request;

        $this->subject = new UserSession();
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
        );

        parent::tearDown();
    }

    #[Test]
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
    public static function yearAndMonthDataProvider(): array
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

    #[Test]
    #[DataProvider('yearAndMonthDataProvider')]
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
