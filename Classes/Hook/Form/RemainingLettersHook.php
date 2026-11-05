<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Hook\Form;

use JWeiland\Events2\Form\RemainingLettersAssets;
use JWeiland\Events2\Form\RichTextFormElementConfigurator;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\CMS\Form\Domain\Model\Renderable\RootRenderableInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;

/**
 * Adds the counter of remaining letters to each textarea with the attribute
 * "maxlength" ("properties.fluidAdditionalAttributes.maxlength"), which is
 * rendered on the current page of the form. The translated label is added as
 * data attribute. Rich text elements are skipped, as the editor replaces the
 * textarea. Without JavaScript, the maxlength and the validators still apply.
 */
final readonly class RemainingLettersHook
{
    public const DATA_ATTRIBUTE = 'data-events2-remaining-letters';

    private const FORM_ELEMENT_TYPE = 'Textarea';

    public function __construct(
        private RemainingLettersAssets $remainingLettersAssets,
    ) {}

    public function beforeRendering(FormRuntime $formRuntime, RootRenderableInterface $renderable): void
    {
        if (!$renderable instanceof FormElementInterface || !$this->hasMaxLength($renderable)) {
            return;
        }

        $renderable->setProperty('fluidAdditionalAttributes', [
            self::DATA_ATTRIBUTE => $this->remainingLettersAssets->getLabel(),
        ]);
        $this->remainingLettersAssets->load();
    }

    private function hasMaxLength(FormElementInterface $formElement): bool
    {
        $properties = $formElement->getProperties();
        if ($formElement->getType() !== self::FORM_ELEMENT_TYPE
            || ($properties['dbMapping']['dataType'] ?? '') === RichTextFormElementConfigurator::DATA_TYPE
        ) {
            return false;
        }

        $maxLength = $properties['fluidAdditionalAttributes']['maxlength'] ?? 0;

        return is_numeric($maxLength) && (int)$maxLength > 0;
    }
}
