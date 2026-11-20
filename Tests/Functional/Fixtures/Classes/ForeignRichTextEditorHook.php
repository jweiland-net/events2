<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Fixtures\Classes;

use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\CMS\Form\Domain\Model\Renderable\RootRenderableInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;

/**
 * Rich text editor hook of another extension, which also acts on elements
 * with "dataType: richText", but on its own forms only
 */
final class ForeignRichTextEditorHook
{
    public const OWNER = 'foreign';

    public const DATA_ATTRIBUTE = 'data-foreign-rich-text';

    public function beforeRendering(FormRuntime $formRuntime, RootRenderableInterface $renderable): void
    {
        if (($formRuntime->getFormDefinition()->getRenderingOptions()['formOwner'] ?? '') !== self::OWNER
            || !$renderable instanceof FormElementInterface
            || ($renderable->getProperties()['dbMapping']['dataType'] ?? '') !== 'richText'
        ) {
            return;
        }

        $renderable->setProperty('fluidAdditionalAttributes', [self::DATA_ATTRIBUTE => '{}']);
    }
}
