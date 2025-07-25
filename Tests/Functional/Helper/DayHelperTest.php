<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Helper;

use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Helper\DayHelper;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for DayHelper
 */
class DayHelperTest extends FunctionalTestCase
{
    protected DayHelper $subject;

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

        $this->subject = $this->get(DayHelper::class);
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
        );

        parent::tearDown();
    }

    #[Test]
    public function getDayFromUriReturnsNull(): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);

        $request = new ServerRequest('https://www.example.com/', 'GET');
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $request = $request->withAttribute('frontend.typoscript', $frontendTypoScript);

        $GLOBALS['TYPO3_REQUEST'] = $request;

        self::assertNull(
            $this->subject->getDayFromUri(),
        );
    }

    #[Test]
    public function getDayFromUriWithInvalidDayReturnsNull(): void
    {
        $databaseConnection = $this->getConnectionPool()->getConnectionForTable('tx_events2_domain_model_day');
        $databaseConnection->insert(
            'tx_events2_domain_model_day',
            [
                'uid' => 1,
                'pid' => Events2Constants::PAGE_STORAGE,
            ],
        );

        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);

        $request = new ServerRequest('https://www.example.com/', 'GET');
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $request = $request->withAttribute('frontend.typoscript', $frontendTypoScript);
        $request = $request->withQueryParams([
            'tx_events2_list' => [
                'day' => '12',
            ],
        ]);

        $GLOBALS['TYPO3_REQUEST'] = $request;

        self::assertNull(
            $this->subject->getDayFromUri(),
        );
    }

    #[Test]
    public function getDayFromUriWithValidDayReturnsDay(): void
    {
        $databaseConnection = $this->getConnectionPool()->getConnectionForTable('tx_events2_domain_model_day');
        $databaseConnection->insert(
            'tx_events2_domain_model_day',
            [
                'uid' => 1,
                'pid' => Events2Constants::PAGE_STORAGE,
            ],
        );

        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);

        $request = new ServerRequest('https://www.example.com/', 'GET');
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $request = $request->withAttribute('frontend.typoscript', $frontendTypoScript);
        $request = $request->withQueryParams([
            'tx_events2_list' => [
                'day' => '1',
            ],
        ]);

        $GLOBALS['TYPO3_REQUEST'] = $request;

        $day = $this->subject->getDayFromUri();

        self::assertInstanceOf(
            Day::class,
            $day,
        );
        self::assertSame(
            1,
            $day->getUid(),
        );
    }
}
