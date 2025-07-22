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
use JWeiland\Events2\Tests\Functional\Traits\CacheHashTrait;
use JWeiland\Events2\Tests\Functional\Traits\InsertEventTrait;
use JWeiland\Events2\Tests\Functional\Traits\SiteBasedTestTrait;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case.
 */
class LocationControllerTest extends FunctionalTestCase
{
    use CacheHashTrait;
    use InsertEventTrait;
    use SiteBasedTestTrait;

    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'fluid_styled_content',
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'sjbr/static-info-tables',
        'jweiland/events2',
        __DIR__ . '/../Fixtures/Extensions/site_package',
    ];

    protected const LANGUAGE_PRESETS = [
        'EN' => [
            'id' => 0,
            'title' => 'English',
            'locale' => 'en_US.UTF8',
            'iso' => 'en',
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['BE_USER']->workspace = 0;

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Events2PageTree.csv');

        $this->writeSiteConfiguration(
            'events2-controller-test',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ],
            [],
            ['jweiland/sitepackage'],
        );
    }

    #[Test]
    public function showActionShowsEvent(): void
    {
        $tomorrowMidnight = new \DateTimeImmutable('tomorrow midnight');

        $this->insertEvent(
            title: 'Event Title Tomorrow',
            eventBegin: $tomorrowMidnight,
            timeBegin: '08:15',
            location: 'Marketplace',
        );
        $this->createDayRelations();

        $parameters = [
            'tx_events2_show' => [
                'controller' => 'Location',
                'action' => 'show',
                'location' => 1,
            ],
        ];

        $parameters['cHash'] = $this->generateCacheHash($parameters, Events2Constants::PAGE_SHOW);

        $content = (string)$this->executeFrontendSubRequest(
            (new InternalRequest())
                ->withPageId(Events2Constants::PAGE_SHOW)
                ->withQueryParams($parameters),
        )->getBody();

        self::assertStringContainsString(
            'Marketplace',
            $content,
        );
    }
}
