<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Hook\Form;

use JWeiland\Events2\Form\RichTextEditorAssets;
use JWeiland\Events2\Form\RichTextFormElementConfigurator;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\CMS\Form\Domain\Model\Renderable\RootRenderableInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;

/**
 * Adds the CKEditor to the rich text elements (dataType "richText"), which
 * are rendered on the current page of the form. So the assets are loaded on
 * these pages only. Each textarea gets its own options as JSON.
 */
final readonly class RichTextEditorHook
{
    public const DATA_ATTRIBUTE = 'data-events2-rich-text';

    public function __construct(
        private RichTextEditorAssets $richTextEditorAssets,
    ) {}

    public function beforeRendering(FormRuntime $formRuntime, RootRenderableInterface $renderable): void
    {
        if (
            !$renderable instanceof FormElementInterface
            || ($renderable->getProperties()['dbMapping']['dataType'] ?? '') !== RichTextFormElementConfigurator::DATA_TYPE
        ) {
            return;
        }

        $renderable->setProperty('fluidAdditionalAttributes', [
            self::DATA_ATTRIBUTE => json_encode(
                $this->richTextEditorAssets->getEditorOptions($formRuntime->getRequest()),
                JSON_THROW_ON_ERROR,
            ),
        ]);
        $this->richTextEditorAssets->load();
    }
}
