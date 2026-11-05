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
use JWeiland\Events2\Sanitizer\RichTextSanitizer;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\CMS\Form\Domain\Model\Renderable\RootRenderableInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;

/**
 * Adds the CKEditor to the rich text elements (dataType "richText"), which
 * are rendered on the current page of the form. So the assets are loaded on
 * these pages only. Each textarea gets its own options as JSON.
 *
 * The toolbar of the editor is based on the tags, which the sanitizer allows
 * for the column of the element (see ModifyRichTextSanitizerConfigurationEvent).
 * The column is "properties.dbMapping.column", the table is
 * "properties.dbMapping.table" (default: tx_events2_domain_model_event).
 */
final readonly class RichTextEditorHook
{
    public const DATA_ATTRIBUTE = 'data-events2-rich-text';

    private const DEFAULT_TABLE = 'tx_events2_domain_model_event';

    public function __construct(
        private RichTextEditorAssets $richTextEditorAssets,
        private RichTextSanitizer $richTextSanitizer,
    ) {}

    public function beforeRendering(FormRuntime $formRuntime, RootRenderableInterface $renderable): void
    {
        if (
            !$renderable instanceof FormElementInterface
            || ($renderable->getProperties()['dbMapping']['dataType'] ?? '') !== RichTextFormElementConfigurator::DATA_TYPE
        ) {
            return;
        }

        $dbMapping = $renderable->getProperties()['dbMapping'];
        $configuration = $this->richTextSanitizer->getConfiguration(
            (string)($dbMapping['table'] ?? self::DEFAULT_TABLE),
            (string)($dbMapping['column'] ?? ''),
        );

        $renderable->setProperty('fluidAdditionalAttributes', [
            self::DATA_ATTRIBUTE => json_encode(
                $this->richTextEditorAssets->getEditorOptions($formRuntime->getRequest(), $configuration),
                JSON_THROW_ON_ERROR,
            ),
        ]);
        $this->richTextEditorAssets->load();
    }
}
