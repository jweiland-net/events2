<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Hook\Form;

use JWeiland\Events2\Form\RemainingLettersAssets;
use JWeiland\Events2\Hook\Form\RemainingLettersHook;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case.
 */
class RemainingLettersHookTest extends UnitTestCase
{
    private RemainingLettersAssets&MockObject $remainingLettersAssets;

    private FormRuntime&MockObject $formRuntime;

    private RemainingLettersHook $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->remainingLettersAssets = $this->createMock(RemainingLettersAssets::class);
        $this->remainingLettersAssets->method('getLabel')->willReturn('Noch %s Zeichen');

        $this->formRuntime = $this->createMock(FormRuntime::class);

        $this->subject = new RemainingLettersHook($this->remainingLettersAssets);
    }

    #[Test]
    public function beforeRenderingAddsLabelToTextareaWithMaxLength(): void
    {
        $element = $this->buildElement('Textarea', ['maxlength' => 255], 'plainText');
        $this->remainingLettersAssets->expects(self::once())->method('load');

        $this->subject->beforeRendering($this->formRuntime, $element);

        self::assertSame(
            ['maxlength' => 255, 'data-events2-remaining-letters' => 'Noch %s Zeichen'],
            $element->getProperties()['fluidAdditionalAttributes'],
        );
    }

    public static function elementsWithoutCounterDataProvider(): array
    {
        return [
            'textarea without maxlength' => ['Textarea', ['class' => 'x'], 'plainText'],
            'textarea with empty maxlength' => ['Textarea', ['maxlength' => ''], 'plainText'],
            'textarea with maxlength 0' => ['Textarea', ['maxlength' => '0'], 'plainText'],
            'textarea with invalid maxlength' => ['Textarea', ['maxlength' => 'foo'], 'plainText'],
            'rich text element with maxlength' => ['Textarea', ['maxlength' => 255], 'richText'],
            'text field with maxlength' => ['Text', ['maxlength' => 255], 'plainText'],
        ];
    }

    #[Test]
    #[DataProvider('elementsWithoutCounterDataProvider')]
    public function beforeRenderingIgnoresElementsWithoutCounter(
        string $type,
        array $additionalAttributes,
        string $dataType,
    ): void {
        $element = $this->buildElement($type, $additionalAttributes, $dataType);
        $this->remainingLettersAssets->expects(self::never())->method('load');

        $this->subject->beforeRendering($this->formRuntime, $element);

        self::assertSame($additionalAttributes, $element->getProperties()['fluidAdditionalAttributes']);
    }

    #[Test]
    public function beforeRenderingIgnoresFormDefinition(): void
    {
        $this->remainingLettersAssets->expects(self::never())->method('load');

        $this->subject->beforeRendering($this->formRuntime, new FormDefinition('newEvent'));
    }

    private function buildElement(string $type, array $additionalAttributes, string $dataType): GenericFormElement
    {
        $element = new GenericFormElement('short-description', $type);
        $element->setParentRenderable(new FormDefinition('newEvent'));
        $element->setProperty('dbMapping', ['column' => 'teaser', 'dataType' => $dataType]);
        $element->setProperty('fluidAdditionalAttributes', $additionalAttributes);

        return $element;
    }
}
