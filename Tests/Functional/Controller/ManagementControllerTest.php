<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Controller;

use JWeiland\Events2\Tests\Functional\Events2Constants;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Core\Bootstrap;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case.
 */
class ManagementControllerTest extends FunctionalTestCase
{
    protected ServerRequestInterface $serverRequest;

    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'sjbr/static-info-tables',
        'jweiland/events2',
    ];

    protected function setUp(): void
    {
        self::markTestIncomplete('ManagementControllerTest not updated until right now');

        parent::setUp();

        $this->importDataSet('ntf://Database/pages.xml');
        $this->setUpFrontendRootPage(1, [__DIR__ . '/../Fixtures/TypoScript/setup.typoscript']);

        $this->serverRequest = $this->getServerRequestForFrontendMode();

        $this->getDatabaseConnection()->insertArray(
            'fe_users',
            [
                'pid' => Events2Constants::PAGE_STORAGE,
                'username' => 'froemken',
                'tx_events2_organizer' => 1,
            ],
        );

        $this->getDatabaseConnection()->insertArray(
            'tx_events2_domain_model_organizer',
            [
                'pid' => Events2Constants::PAGE_STORAGE,
                'organizer' => 'Stefan',
            ],
        );

        $date = new \DateTimeImmutable('midnight');
        $this->getDatabaseConnection()->insertArray(
            'tx_events2_domain_model_event',
            [
                'pid' => Events2Constants::PAGE_STORAGE,
                'event_type' => 'single',
                'event_begin' => (int)$date->format('U'),
                'title' => 'Today',
                'organizers' => '1',
            ],
        );

        $this->getDatabaseConnection()->insertArray(
            'tx_events2_event_organizer_mm',
            [
                'uid_local' => 1,
                'uid_foreign' => 1,
            ],
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->request,
            $GLOBALS['TSFE'],
        );

        parent::tearDown();
    }

    #[Test]
    public function processRequestWithNewActionWillCollectSelectableCategories(): void
    {
        $this->startUpTSFE($this->serverRequest);

        $GLOBALS['TSFE']->fe_user->user = $this->getDatabaseConnection()->selectSingleRow(
            '*',
            'fe_users',
            'uid = 1',
        );

        $extbaseBootstrap = GeneralUtility::makeInstance(Bootstrap::class);
        $content = $extbaseBootstrap->run(
            '',
            [
                'extensionName' => 'Events2',
                'pluginName' => 'Management',
                'format' => 'txt',
                'settings' => [
                    'userGroup' => '1',
                ],
            ],
        );

        self::assertStringContainsString(
            'Event Title 1: Today',
            $content,
        );
        self::assertStringContainsString(
            'tx_events2_management%5Baction%5D=edit&amp;tx_events2_management%5Bcontroller%5D=Management',
            $content,
        );
    }
}
