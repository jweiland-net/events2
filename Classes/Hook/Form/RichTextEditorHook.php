<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Hook\Form;

use JWeiland\Events2\Form\FormOwner;
use JWeiland\Events2\Form\RichTextEditorOptions;
use JWeiland\Events2\Form\RichTextFormElementConfigurator;
use JWeiland\Events2\Sanitizer\RichTextSanitizer;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\CMS\Form\Domain\Model\Renderable\RootRenderableInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;

/**
 * Adds the options of the CKEditor as JSON to the rich text elements
 * (dataType "richText"), which are rendered on the current page of a form of
 * events2 (see FormOwner). The partial RichTextEditor/Textarea.html renders the
 * textarea and loads the assets with the partial RichTextEditor/LoadAssets.html.
 * The folder of the translations of CKEditor is the property
 * "richTextEditor.translationsPath" of the element, see
 * RichTextFormElementConfigurator.
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
        private RichTextEditorOptions $richTextEditorOptions,
        private RichTextSanitizer $richTextSanitizer,
    ) {}

    public function beforeRendering(FormRuntime $formRuntime, RootRenderableInterface $renderable): void
    {
        if (
            !FormOwner::isEvents2Form($formRuntime->getFormDefinition())
            || !$renderable instanceof FormElementInterface
            || ($renderable->getProperties()['dbMapping']['dataType'] ?? '') !== RichTextFormElementConfigurator::DATA_TYPE
        ) {
            return;
        }

        $properties = $renderable->getProperties();
        $configuration = $this->richTextSanitizer->getConfiguration(
            (string)($properties['dbMapping']['table'] ?? self::DEFAULT_TABLE),
            (string)($properties['dbMapping']['column'] ?? ''),
        );
        $editorOptions = $this->richTextEditorOptions->getEditorOptions(
            $formRuntime->getRequest(),
            $configuration,
            (string)($properties['richTextEditor']['translationsPath'] ?? ''),
        );

        $renderable->setProperty('fluidAdditionalAttributes', [
            self::DATA_ATTRIBUTE => json_encode($editorOptions, JSON_THROW_ON_ERROR),
        ]);
    }
}
