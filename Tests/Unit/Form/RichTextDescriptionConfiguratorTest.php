<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Form;

use JWeiland\Events2\Form\RichTextDescriptionConfigurator;
use JWeiland\Events2\Form\RichTextEditorAssets;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case.
 */
class RichTextDescriptionConfiguratorTest extends UnitTestCase
{
    private const EDITOR_OPTIONS = ['language' => 'de'];

    private RichTextDescriptionConfigurator $subject;

    private RichTextEditorAssets $richTextEditorAssets;

    protected function setUp(): void
    {
        parent::setUp();

        $this->richTextEditorAssets = $this->createMock(RichTextEditorAssets::class);
        $this->richTextEditorAssets->method('getEditorOptions')->willReturn(self::EDITOR_OPTIONS);
        $this->subject = new RichTextDescriptionConfigurator($this->richTextEditorAssets);
    }

    #[Test]
    public function isEnabledWithoutRequestReturnsFalse(): void
    {
        self::assertFalse($this->subject->isEnabled(null));
    }

    #[Test]
    public function isEnabledWithDisabledSettingReturnsFalse(): void
    {
        self::assertFalse($this->subject->isEnabled($this->buildRequest('0')));
    }

    #[Test]
    public function isEnabledWithoutTypoScriptReturnsFalse(): void
    {
        self::assertFalse($this->subject->isEnabled(new ServerRequest('https://example.com/')));
    }

    #[Test]
    public function configureSwitchesDescriptionElementToRichText(): void
    {
        $element = $this->findDescriptionElement($this->configure()['renderables']);

        self::assertSame('richText', $element['properties']['dbMapping']['dataType']);
        self::assertSame(
            json_encode(self::EDITOR_OPTIONS),
            $element['properties']['fluidAdditionalAttributes']['data-events2-rich-text'],
        );
    }

    #[Test]
    public function configureSanitizesDescriptionInSaveEventFinisher(): void
    {
        $elements = $this->getEventElementsOfFinisher($this->configure());

        self::assertTrue($elements['detail-description']['sanitizeRichText']);
        self::assertArrayNotHasKey('convertPlainTextToHtml', $elements['detail-description']);
        self::assertTrue($elements['short-description']['normalizePlainText']);
    }

    #[Test]
    public function configureKeepsOtherElements(): void
    {
        $configuration = $this->getFormDefinition();
        $configured = $this->configure();

        self::assertSame(
            $this->getEventElementsOfFinisher($configuration)['title'],
            $this->getEventElementsOfFinisher($configured)['title'],
        );
        self::assertSame(count($configuration['renderables']), count($configured['renderables']));
    }

    #[Test]
    public function configureLoadsAssets(): void
    {
        $this->richTextEditorAssets->expects(self::once())->method('load');

        $this->configure();
    }

    /**
     * @return array<string, mixed>
     */
    private function configure(): array
    {
        return $this->subject->configure($this->getFormDefinition(), new ServerRequest('https://example.com/'));
    }

    /**
     * @return array<string, mixed>
     */
    private function getFormDefinition(): array
    {
        return Yaml::parseFile(__DIR__ . '/../../../Configuration/Form/NewEvent.form.yaml');
    }

    /**
     * @param array<int|string, mixed> $renderables
     * @return array<string, mixed>
     */
    private function findDescriptionElement(array $renderables): array
    {
        foreach ($renderables as $renderable) {
            if (($renderable['identifier'] ?? '') === 'detail-description') {
                return $renderable;
            }
            $element = $this->findDescriptionElement($renderable['renderables'] ?? []);
            if ($element !== []) {
                return $element;
            }
        }

        return [];
    }

    /**
     * @param array<string, mixed> $configuration
     * @return array<string, array<string, mixed>>
     */
    private function getEventElementsOfFinisher(array $configuration): array
    {
        foreach ($configuration['finishers'][1]['options'] as $option) {
            if (($option['table'] ?? '') === 'tx_events2_domain_model_event') {
                return $option['elements'];
            }
        }

        return [];
    }

    private function buildRequest(string $richTextDescription): ServerRequest
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([
            'plugin.' => [
                'tx_events2.' => [
                    'settings.' => [
                        'new.' => [
                            'richTextDescription' => $richTextDescription,
                        ],
                    ],
                ],
            ],
        ]);

        return (new ServerRequest('https://example.com/'))->withAttribute('frontend.typoscript', $frontendTypoScript);
    }
}
