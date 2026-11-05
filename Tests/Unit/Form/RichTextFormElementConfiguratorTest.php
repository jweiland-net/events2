<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Form;

use JWeiland\Events2\Form\RichTextFormElementConfigurator;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case.
 */
class RichTextFormElementConfiguratorTest extends UnitTestCase
{
    private RichTextFormElementConfigurator $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->buildSubject(true);
    }

    #[Test]
    public function isEnabledWithoutRequestReturnsFalse(): void
    {
        self::assertFalse($this->subject->isEnabled(null));
    }

    #[Test]
    public function isEnabledWithoutTypoScriptReturnsFalse(): void
    {
        self::assertFalse($this->subject->isEnabled(new ServerRequest('https://example.com/')));
    }

    #[Test]
    public function isEnabledWithDisabledSettingReturnsFalse(): void
    {
        self::assertFalse($this->subject->isEnabled($this->buildRequest('0')));
    }

    #[Test]
    public function isEnabledWithoutRteCkeditorReturnsFalse(): void
    {
        self::assertFalse($this->buildSubject(false)->isEnabled($this->buildRequest('1')));
    }

    #[Test]
    public function isEnabledWithEnabledSettingReturnsTrue(): void
    {
        self::assertTrue($this->subject->isEnabled($this->buildRequest('1')));
    }

    #[Test]
    public function getConfiguredElementIdentifiersReturnsTrimmedIdentifiers(): void
    {
        self::assertSame(
            ['detail-description', 'program'],
            $this->subject->getConfiguredElementIdentifiers($this->buildRequest('1', ' detail-description, program,')),
        );
    }

    #[Test]
    public function configureWithDefaultElementSwitchesDescriptionToRichText(): void
    {
        $configuration = $this->subject->configure($this->getFormDefinition(), $this->buildRequest('1'));

        self::assertSame('richText', $this->getDataType($configuration, 'detail-description'));
        self::assertSame('plainText', $this->getDataType($configuration, 'short-description'));

        $elements = $this->getEventElementsOfFinisher($configuration);
        self::assertTrue($elements['detail-description']['sanitizeRichText']);
        self::assertArrayNotHasKey('convertPlainTextToHtml', $elements['detail-description']);
        self::assertTrue($elements['short-description']['normalizePlainText']);
        self::assertArrayNotHasKey('sanitizeRichText', $elements['short-description']);
    }

    #[Test]
    public function configureWithTwoConfiguredElementsSwitchesBothToRichText(): void
    {
        $configuration = $this->subject->configure(
            $this->getFormDefinition(),
            $this->buildRequest('1', 'short-description, detail-description'),
        );

        $elements = $this->getEventElementsOfFinisher($configuration);
        foreach (['short-description', 'detail-description'] as $identifier) {
            self::assertSame('richText', $this->getDataType($configuration, $identifier));
            self::assertTrue($elements[$identifier]['sanitizeRichText']);
            self::assertArrayNotHasKey('normalizePlainText', $elements[$identifier]);
            self::assertArrayNotHasKey('convertPlainTextToHtml', $elements[$identifier]);
        }
    }

    #[Test]
    public function configureWithDataTypeRichTextInFormDefinitionSwitchesElementToRichText(): void
    {
        $formDefinition = $this->addProgramElement($this->getFormDefinition(), ['convertPlainTextToHtml' => true]);

        $configuration = $this->subject->configure($formDefinition, $this->buildRequest('1', 'detail-description'));

        $elements = $this->getEventElementsOfFinisher($configuration);
        self::assertSame('richText', $this->getDataType($configuration, 'program'));
        self::assertSame(['mapOnDatabaseColumn' => 'tx_myext_program', 'sanitizeRichText' => true], $elements['program']);
        self::assertTrue($elements['detail-description']['sanitizeRichText']);
    }

    #[Test]
    public function configureWhileDisabledFallsBackToPlainText(): void
    {
        $formDefinition = $this->addProgramElement($this->getFormDefinition(), ['sanitizeRichText' => true]);

        $configuration = $this->subject->configure($formDefinition, $this->buildRequest('0'));

        self::assertSame('plainText', $this->getDataType($configuration, 'program'));
        self::assertSame('plainText', $this->getDataType($configuration, 'detail-description'));

        $elements = $this->getEventElementsOfFinisher($configuration);
        self::assertSame(['mapOnDatabaseColumn' => 'tx_myext_program', 'convertPlainTextToHtml' => true], $elements['program']);
        self::assertTrue($elements['detail-description']['convertPlainTextToHtml']);
        self::assertArrayNotHasKey('sanitizeRichText', $elements['detail-description']);
    }

    #[Test]
    public function configureKeepsOtherElements(): void
    {
        $formDefinition = $this->getFormDefinition();
        $configuration = $this->subject->configure($formDefinition, $this->buildRequest('1'));

        self::assertSame($this->findElement($formDefinition, 'title'), $this->findElement($configuration, 'title'));
        self::assertSame(
            $this->getEventElementsOfFinisher($formDefinition)['title'],
            $this->getEventElementsOfFinisher($configuration)['title'],
        );
    }

    private function buildSubject(bool $isRteCkeditorActive): RichTextFormElementConfigurator
    {
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('isPackageActive')->with('rte_ckeditor')->willReturn($isRteCkeditorActive);

        return new RichTextFormElementConfigurator($packageManager);
    }

    /**
     * @return array<string, mixed>
     */
    private function getFormDefinition(): array
    {
        return Yaml::parseFile(__DIR__ . '/../../../Configuration/Form/NewEvent.form.yaml');
    }

    /**
     * Adds an own element with "dataType: richText" and maps it in the SaveEventFinisher
     *
     * @param array<string, mixed> $formDefinition
     * @param array<string, mixed> $mapping
     * @return array<string, mixed>
     */
    private function addProgramElement(array $formDefinition, array $mapping): array
    {
        $formDefinition['renderables'][1]['renderables'][] = [
            'type' => 'Textarea',
            'identifier' => 'program',
            'properties' => ['dbMapping' => ['column' => 'tx_myext_program', 'dataType' => 'richText']],
        ];
        foreach ($formDefinition['finishers'][1]['options'] as $key => $option) {
            if (($option['table'] ?? '') === 'tx_events2_domain_model_event') {
                $formDefinition['finishers'][1]['options'][$key]['elements']['program']
                    = ['mapOnDatabaseColumn' => 'tx_myext_program'] + $mapping;
            }
        }

        return $formDefinition;
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private function getDataType(array $configuration, string $identifier): string
    {
        return (string)($this->findElement($configuration, $identifier)['properties']['dbMapping']['dataType'] ?? '');
    }

    /**
     * @param array<string, mixed> $configuration
     * @return array<string, mixed>
     */
    private function findElement(array $configuration, string $identifier): array
    {
        foreach ($configuration['renderables'] ?? [] as $renderable) {
            if (($renderable['identifier'] ?? '') === $identifier) {
                return $renderable;
            }
            $element = $this->findElement($renderable, $identifier);
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

    private function buildRequest(string $enable, string $elements = 'detail-description'): ServerRequest
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([
            'plugin.' => [
                'tx_events2.' => [
                    'settings.' => [
                        'new.' => [
                            'richText.' => [
                                'enable' => $enable,
                                'elements' => $elements,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        return (new ServerRequest('https://example.com/'))->withAttribute('frontend.typoscript', $frontendTypoScript);
    }
}
