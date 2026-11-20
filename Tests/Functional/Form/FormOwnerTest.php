<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Form;

use JWeiland\Events2\Form\FormOwner;
use JWeiland\Events2\Hook\Form\RemainingLettersHook;
use JWeiland\Events2\Tests\Functional\Fixtures\Classes\ForeignRichTextEditorHook;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement;
use TYPO3\CMS\Form\Domain\Model\FormElements\Page;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The hooks of EXT:form are called for all forms of the installation. The
 * hooks of events2 must act on forms of events2 only.
 */
class FormOwnerTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'form',
        'reactions',
        'rte_ckeditor',
    ];

    protected array $testExtensionsToLoad = [
        'jweiland/events2',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeRendering'][1763600000]
            = ForeignRichTextEditorHook::class;
    }

    #[Test]
    public function ownFormGetsExactlyOneRichTextEditorAndCounterOfRemainingLetters(): void
    {
        $formDefinition = $this->buildFormDefinition(FormOwner::EVENTS2);

        $this->renderForm($formDefinition);

        self::assertSame(['data-events2-rich-text'], $this->getRichTextAttributes($formDefinition));
        self::assertArrayHasKey(RemainingLettersHook::DATA_ATTRIBUTE, $this->getAttributes($formDefinition, 'short-description'));
    }

    #[Test]
    public function formOfOtherExtensionGetsItsOwnRichTextEditorOnly(): void
    {
        $formDefinition = $this->buildFormDefinition(ForeignRichTextEditorHook::OWNER);

        $this->renderForm($formDefinition);

        self::assertSame([ForeignRichTextEditorHook::DATA_ATTRIBUTE], $this->getRichTextAttributes($formDefinition));
        self::assertSame(['maxlength' => 255], $this->getAttributes($formDefinition, 'short-description'));
    }

    #[Test]
    public function formOfCoreIsNotModified(): void
    {
        $formDefinition = $this->buildFormDefinition('');

        $this->renderForm($formDefinition);

        self::assertSame(['class' => 'form-control'], $this->getAttributes($formDefinition, 'detail-description'));
        self::assertSame(['maxlength' => 255], $this->getAttributes($formDefinition, 'short-description'));
    }

    /**
     * Calls all registered "beforeRendering" hooks for each element like FormRuntime does
     */
    private function renderForm(FormDefinition $formDefinition): void
    {
        $formRuntime = $this->createMock(FormRuntime::class);
        $formRuntime->method('getFormDefinition')->willReturn($formDefinition);
        $formRuntime->method('getRequest')->willReturn($this->createMock(Request::class));

        foreach ($formDefinition->getRenderablesRecursively() as $renderable) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeRendering'] ?? [] as $className) {
                $hook = GeneralUtility::makeInstance($className);
                if (method_exists($hook, 'beforeRendering')) {
                    $hook->beforeRendering($formRuntime, $renderable);
                }
            }
        }
    }

    /**
     * @return string[]
     */
    private function getRichTextAttributes(FormDefinition $formDefinition): array
    {
        return array_values(array_filter(
            array_keys($this->getAttributes($formDefinition, 'detail-description')),
            static fn(string $attribute): bool => str_ends_with($attribute, '-rich-text'),
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function getAttributes(FormDefinition $formDefinition, string $identifier): array
    {
        $element = $formDefinition->getElementByIdentifier($identifier);
        self::assertInstanceOf(FormElementInterface::class, $element);

        return $element->getProperties()['fluidAdditionalAttributes'] ?? [];
    }

    /**
     * A form with a rich text element and a teaser with a maxlength like the
     * event form. $owner is the rendering option "formOwner".
     */
    private function buildFormDefinition(string $owner): FormDefinition
    {
        $formDefinition = new FormDefinition('contact', [
            'formElementsDefinition' => [
                'Form' => [],
                'Page' => ['implementationClassName' => Page::class],
                'Textarea' => ['implementationClassName' => GenericFormElement::class],
            ],
        ]);
        if ($owner !== '') {
            $formDefinition->setRenderingOption(FormOwner::RENDERING_OPTION, $owner);
        }

        $page = $formDefinition->createPage('page');
        $description = $page->createElement('detail-description', 'Textarea');
        $description->setProperty('dbMapping', ['column' => 'detail_information', 'dataType' => 'richText']);
        $description->setProperty('fluidAdditionalAttributes', ['class' => 'form-control']);

        $teaser = $page->createElement('short-description', 'Textarea');
        $teaser->setProperty('dbMapping', ['column' => 'teaser', 'dataType' => 'plainText']);
        $teaser->setProperty('fluidAdditionalAttributes', ['maxlength' => 255]);

        return $formDefinition;
    }
}
