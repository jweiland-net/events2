<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Form;

use JWeiland\Events2\Hook\Form\RemainingLettersHook;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use JWeiland\Events2\Tests\Functional\Traits\CacheHashTrait;
use JWeiland\Events2\Tests\Functional\Traits\CreatePostStreamBodyTrait;
use JWeiland\Events2\Tests\Functional\Traits\SiteBasedTestTrait;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Form\Domain\Runtime\FormState;
use TYPO3\CMS\Form\Security\HashScope;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The JavaScript module of the counter of remaining letters is loaded by
 * the partial RemainingLetters/LoadAssets.html on pages of the event form
 * with a textarea with a maxlength only. The teaser is on the second page.
 */
class RemainingLettersTest extends FunctionalTestCase
{
    use CacheHashTrait;
    use CreatePostStreamBodyTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => [
            'id' => 0,
            'title' => 'English',
            'locale' => 'en_US.UTF8',
            'iso' => 'en',
        ],
    ];

    private const MODULE = '@jweiland/events2/remaining-letters.js';

    protected array $coreExtensionsToLoad = [
        'fluid_styled_content',
        'form',
        'reactions',
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
        $this->writeSiteConfiguration(
            'events2-remaining-letters-test',
            $this->buildSiteConfiguration(1, '/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/')],
            [],
            ['jweiland/sitepackage'],
        );
    }

    #[Test]
    public function pageWithoutTextareaWithMaxLengthDoesNotLoadModule(): void
    {
        $content = $this->renderPage([]);

        self::assertStringContainsString('choose-event-type', $content);
        self::assertStringNotContainsString(RemainingLettersHook::DATA_ATTRIBUTE, $content);
        self::assertStringNotContainsString('remaining-letters.js', $content);
    }

    #[Test]
    public function pageWithTeaserLoadsModuleOnceAndAddsLabel(): void
    {
        $content = $this->renderPage($this->buildBodyOfFirstPage());

        self::assertMatchesRegularExpression(
            '#<textarea[^>]*' . RemainingLettersHook::DATA_ATTRIBUTE . '="%s characters left"[^>]*maxlength="255"|<textarea[^>]*maxlength="255"[^>]*' . RemainingLettersHook::DATA_ATTRIBUTE . '="%s characters left"#',
            $content,
        );
        self::assertStringContainsString('"' . self::MODULE . '":', $content);
        self::assertSame(1, preg_match_all('#<script type="module"[^>]+src="[^"]+/remaining-letters\.js#', $content));
    }

    /**
     * The choice of the first page is submitted, so the second page with the teaser is rendered
     *
     * @return array<string, mixed>
     */
    private function buildBodyOfFirstPage(): array
    {
        $formState = new FormState();
        $formState->setLastDisplayedPageIndex(0);

        return [
            'tx_events2_management' => [
                'newEvent' => [
                    '__state' => $this->get(HashService::class)->appendHmac(
                        base64_encode(serialize($formState)),
                        HashScope::FormState->prefix(),
                    ),
                    '__currentPage' => '1',
                    'choose-event-type' => 'single',
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $body
     */
    private function renderPage(array $body): string
    {
        $parameters = [
            'tx_events2_management' => [
                'controller' => 'Management',
                'action' => 'perform',
            ],
        ];
        $parameters['cHash'] = $this->generateCacheHash($parameters, Events2Constants::PAGE_MANAGEMENT);

        $request = (new InternalRequest())
            ->withPageId(Events2Constants::PAGE_MANAGEMENT)
            ->withQueryParams($parameters);
        if ($body !== []) {
            $request = $request
                ->withMethod('POST')
                ->withAddedHeader('Content-type', 'application/x-www-form-urlencoded')
                ->withBody($this->createBodyFromArray($body));
        }

        return (string)$this->executeFrontendSubRequest(
            $request,
            (new InternalRequestContext())->withFrontendUserId(1),
        )->getBody();
    }
}
