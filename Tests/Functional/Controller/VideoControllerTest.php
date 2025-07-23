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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case.
 */
class VideoControllerTest extends FunctionalTestCase
{
    use CacheHashTrait;
    use InsertEventTrait;
    use SiteBasedTestTrait;

    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'fluid_styled_content',
        'form',
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

        date_default_timezone_set('Europe/Berlin');

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

    public static function pluginTypeDataProvider(): array
    {
        return [
            'Page with events2 plugin: events2_list' => ['tx_events2_list', Events2Constants::PAGE_LIST],
            'Page with events2 plugin: events2_show' => ['tx_events2_show', Events2Constants::PAGE_SHOW],
        ];
    }

    #[Test]
    #[DataProvider('pluginTypeDataProvider')]
    public function showActionShowsLocation(string $pluginNamespace, int $pageUid): void
    {
        $videoId = 'bIuds49uJEg';
        $tomorrowMidnight = new \DateTimeImmutable('tomorrow midnight');

        $this->insertEvent(
            title: 'Event Title Tomorrow',
            eventBegin: $tomorrowMidnight,
            videoLink: 'https://www.youtube.com/watch?v=' . $videoId,
        );
        $this->createDayRelations();

        $parameters = [
            $pluginNamespace => [
                'controller' => 'Video',
                'action' => 'show',
                'event' => 1,
            ],
        ];

        $parameters['cHash'] = $this->generateCacheHash($parameters, $pageUid);

        $content = (string)$this->executeFrontendSubRequest(
            (new InternalRequest())
                ->withPageId($pageUid)
                ->withQueryParams($parameters),
        )->getBody();

        self::assertStringContainsString(
            'Video Link',
            $content,
        );
        self::assertStringContainsString(
            '<iframe width="560" height="315" src="//www.youtube.com/embed/' . $videoId . '" frameborder="0" allowfullscreen></iframe>',
            $content,
        );
    }
}
