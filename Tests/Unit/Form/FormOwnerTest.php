<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Form;

use JWeiland\Events2\Form\FormOwner;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement;
use TYPO3\CMS\Form\Domain\Model\FormElements\Page;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case.
 */
class FormOwnerTest extends UnitTestCase
{
    #[Test]
    public function markConfigurationSetsRenderingOption(): void
    {
        self::assertSame(
            ['renderingOptions' => ['submitButtonLabel' => 'Save', 'formOwner' => 'events2']],
            FormOwner::markConfiguration(['renderingOptions' => ['submitButtonLabel' => 'Save', 'formOwner' => 'other']]),
        );
    }

    #[Test]
    public function isEvents2FormWithOwnFormReturnsTrue(): void
    {
        self::assertTrue(FormOwner::isEvents2Form($this->buildFormDefinition('events2')));
    }

    #[Test]
    public function isEvents2FormWithOtherFormReturnsFalse(): void
    {
        self::assertFalse(FormOwner::isEvents2Form($this->buildFormDefinition('yellowpages2')));
        self::assertFalse(FormOwner::isEvents2Form($this->buildFormDefinition('')));
        self::assertFalse(FormOwner::isEvents2Form(null));
    }

    #[Test]
    public function isRenderableOfEvents2FormWithElementOfOwnFormReturnsTrue(): void
    {
        $formDefinition = $this->buildFormDefinition('events2');

        self::assertTrue(FormOwner::isRenderableOfEvents2Form($formDefinition));
        self::assertTrue(FormOwner::isRenderableOfEvents2Form($this->getDescription($formDefinition)));
    }

    #[Test]
    public function isRenderableOfEvents2FormWithElementOfOtherFormReturnsFalse(): void
    {
        self::assertFalse(FormOwner::isRenderableOfEvents2Form($this->getDescription($this->buildFormDefinition('yellowpages2'))));
        self::assertFalse(FormOwner::isRenderableOfEvents2Form($this->getDescription($this->buildFormDefinition(''))));
    }

    #[Test]
    public function isRenderableOfEvents2FormWithElementWithoutFormReturnsFalse(): void
    {
        self::assertFalse(FormOwner::isRenderableOfEvents2Form(new GenericFormElement('description', 'Textarea')));
    }

    private function getDescription(FormDefinition $formDefinition): GenericFormElement
    {
        $element = $formDefinition->getElementByIdentifier('description');
        self::assertInstanceOf(GenericFormElement::class, $element);

        return $element;
    }

    private function buildFormDefinition(string $owner): FormDefinition
    {
        $formDefinition = new FormDefinition('form', [
            'formElementsDefinition' => [
                'Form' => [],
                'Page' => ['implementationClassName' => Page::class],
                'Textarea' => ['implementationClassName' => GenericFormElement::class],
            ],
        ]);
        if ($owner !== '') {
            $formDefinition->setRenderingOption(FormOwner::RENDERING_OPTION, $owner);
        }
        $formDefinition->createPage('page')->createElement('description', 'Textarea');

        return $formDefinition;
    }
}
