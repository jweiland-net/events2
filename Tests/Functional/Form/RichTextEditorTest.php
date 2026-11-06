<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Form;

use JWeiland\Events2\Tests\Functional\Events2Constants;
use JWeiland\Events2\Tests\Functional\Traits\CacheHashTrait;
use JWeiland\Events2\Tests\Functional\Traits\SiteBasedTestTrait;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Renders the first page of the form based on EXT:form with and without the
 * rich text editor. The description is on the second page of the multistep
 * form. So the element "choose-event-type" of the first page is configured
 * as rich text element to test the rendering of the assets.
 */
class RichTextEditorTest extends FunctionalTestCase
{
    use CacheHashTrait;
    use SiteBasedTestTrait;

    private const SITE_IDENTIFIER = 'events2-rich-text-test';

    protected const LANGUAGE_PRESETS = [
        'DE' => [
            'id' => 0,
            'title' => 'Deutsch',
            'locale' => 'de_DE.UTF8',
            'iso' => 'de',
        ],
    ];

    protected array $coreExtensionsToLoad = [
        'fluid_styled_content',
        'form',
        'reactions',
        'rte_ckeditor',
    ];

    protected array $testExtensionsToLoad = [
        'jweiland/events2',
        __DIR__ . '/../Fixtures/Extensions/site_package',
    ];

    protected array $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'events2' => [
                'emailFromAddress' => 'projects@example.com',
                'emailFromName' => 'Events2',
                'emailToAddress' => 'hosting@example.com',
                'emailToName' => 'Events2',
            ],
        ],
        'SYS' => [
            'phpTimeZone' => Events2Constants::PHP_TIMEZONE,
        ],
    ];

    protected array $additionalFoldersToCreate = [
        'fileadmin/Extensions/events2',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Events2PageTree.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/fe_users.csv');

    }

    #[Test]
    public function formWithoutRichTextEditorDoesNotLoadEditor(): void
    {
        $content = $this->renderForm(['elements' => 'choose-event-type']);

        self::assertStringContainsString('choose-event-type', $content);
        self::assertStringNotContainsString('data-events2-rich-text', $content);
        self::assertStringNotContainsString('<script type="importmap"', $content);
        self::assertStringNotContainsString('rich-text-editor.js', $content);
    }

    #[Test]
    public function pageWithoutRichTextElementDoesNotLoadEditor(): void
    {
        // Default element "detail-description" is on the second page
        $content = $this->renderForm(['enable' => true]);

        self::assertStringContainsString('choose-event-type', $content);
        self::assertStringNotContainsString('data-events2-rich-text', $content);
        self::assertStringNotContainsString('<script type="importmap"', $content);
    }

    #[Test]
    public function pageWithRichTextElementRendersImportMapAndAssets(): void
    {
        $content = $this->renderForm(['enable' => true, 'elements' => 'choose-event-type']);

        self::assertStringContainsString('data-events2-rich-text="{&quot;language&quot;:&quot;de&quot;', $content);
        self::assertStringContainsString(
            '&quot;allowedTags&quot;:[&quot;p&quot;,&quot;br&quot;,&quot;strong&quot;,&quot;em&quot;,&quot;h2&quot;,&quot;h3&quot;,'
            . '&quot;ul&quot;,&quot;ol&quot;,&quot;li&quot;,&quot;a&quot;]',
            $content,
        );
        self::assertStringContainsString('<script type="importmap"', $content);
        self::assertStringContainsString('"@ckeditor/ckeditor5-editor-classic":', $content);
        self::assertStringContainsString('"@jweiland/events2/rich-text-editor.js":', $content);
        self::assertMatchesRegularExpression('#<script type="module"[^>]+src="[^"]+/rich-text-editor\.js#', $content);
        self::assertLessThan(
            strpos($content, '<script type="module"'),
            strpos($content, '<script type="importmap"'),
        );
        self::assertStringContainsString('Css/editor.css', $content);
        self::assertStringContainsString('Css/RichTextEditor.css', $content);
    }

    /**
     * @param array<string, mixed> $richTextSettings Site settings "events2.form.richText.*"
     */
    private function renderForm(array $richTextSettings): string
    {
        // Write the site only once per test. Else the former site is still in runtime caches.
        $this->writeSite(['events2' => ['form' => ['richText' => $richTextSettings]]]);
        // The TypoScript of the site depends on the site settings
        $this->get(CacheManager::class)->flushCaches();

        $parameters = [
            'tx_events2_management' => [
                'controller' => 'Management',
                'action' => 'perform',
            ],
        ];
        $parameters['cHash'] = $this->generateCacheHash($parameters, Events2Constants::PAGE_MANAGEMENT);

        return (string)$this->executeFrontendSubRequest(
            (new InternalRequest())
                ->withPageId(Events2Constants::PAGE_MANAGEMENT)
                ->withQueryParams($parameters),
            (new InternalRequestContext())
                ->withFrontendUserId(1),
        )->getBody();
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function writeSite(array $settings): void
    {
        $this->writeSiteConfiguration(
            self::SITE_IDENTIFIER,
            array_filter($this->buildSiteConfiguration(1, '/') + ['settings' => $settings]),
            [
                $this->buildDefaultLanguageConfiguration('DE', '/'),
            ],
            [],
            ['jweiland/sitepackage'],
        );
    }
}
