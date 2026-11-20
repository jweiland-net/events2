<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Form;

use JWeiland\Events2\Form\RemainingLettersFormElementConfigurator;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Yaml\Yaml;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case.
 */
class RemainingLettersFormElementConfiguratorTest extends UnitTestCase
{
    private RemainingLettersFormElementConfigurator $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new RemainingLettersFormElementConfigurator();
    }

    #[Test]
    public function configureRendersTeaserWithPartialOfCounter(): void
    {
        $configuration = $this->subject->configure($this->getFormDefinition());

        self::assertSame(
            'RemainingLetters/Textarea',
            $this->findElement($configuration, 'short-description')['renderingOptions']['templateName'],
        );
    }

    #[Test]
    public function configureKeepsElementsWithoutMaxLength(): void
    {
        $configuration = $this->subject->configure($this->getFormDefinition());

        self::assertArrayNotHasKey('templateName', $this->findElement($configuration, 'detail-description')['renderingOptions'] ?? []);
        self::assertArrayNotHasKey('templateName', $this->findElement($configuration, 'title')['renderingOptions'] ?? []);
    }

    #[Test]
    public function configureSkipsRichTextElement(): void
    {
        $formDefinition = $this->setTeaserProperty($this->getFormDefinition(), 'dbMapping', ['column' => 'teaser', 'dataType' => 'richText']);

        $teaser = $this->findElement($this->subject->configure($formDefinition), 'short-description');

        self::assertArrayNotHasKey('templateName', $teaser['renderingOptions'] ?? []);
    }

    #[Test]
    public function configureKeepsTemplateNameOfFormDefinition(): void
    {
        $formDefinition = $this->getFormDefinition();
        foreach ($formDefinition['renderables'][1]['renderables'] as $key => $renderable) {
            if ($renderable['identifier'] === 'short-description') {
                $formDefinition['renderables'][1]['renderables'][$key]['renderingOptions']['templateName'] = 'MyTextarea';
            }
        }

        $teaser = $this->findElement($this->subject->configure($formDefinition), 'short-description');

        self::assertSame('MyTextarea', $teaser['renderingOptions']['templateName']);
    }

    /**
     * @return array<string, mixed>
     */
    private function getFormDefinition(): array
    {
        return Yaml::parseFile(__DIR__ . '/../../../Configuration/Form/NewEvent.form.yaml');
    }

    /**
     * @param array<string, mixed> $formDefinition
     * @return array<string, mixed>
     */
    private function setTeaserProperty(array $formDefinition, string $property, mixed $value): array
    {
        foreach ($formDefinition['renderables'][1]['renderables'] as $key => $renderable) {
            if ($renderable['identifier'] === 'short-description') {
                $formDefinition['renderables'][1]['renderables'][$key]['properties'][$property] = $value;
            }
        }

        return $formDefinition;
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
}
