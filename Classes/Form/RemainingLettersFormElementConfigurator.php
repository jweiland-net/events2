<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Form;

/**
 * Textareas with the attribute "maxlength"
 * ("properties.fluidAdditionalAttributes.maxlength") get the counter of
 * remaining letters. They are rendered with the partial
 * RemainingLetters/Textarea.html of the partial root path of the form,
 * which loads the JavaScript module of the counter. Rich text elements are
 * skipped, as the editor replaces the textarea. Values of the form
 * definition are kept. RemainingLettersHook adds the label of the counter
 * while rendering.
 */
readonly class RemainingLettersFormElementConfigurator
{
    /**
     * Partial of textareas with the counter in the partial root path of the form.
     * Overridable with "renderingOptions.templateName" of the element.
     */
    private const TEMPLATE_NAME = 'RemainingLetters/Textarea';

    private const FORM_ELEMENT_TYPE = 'Textarea';

    /**
     * @param array<string, mixed> $configuration Form definition
     * @return array<string, mixed>
     */
    public function configure(array $configuration): array
    {
        if (is_array($configuration['renderables'] ?? null)) {
            $configuration['renderables'] = $this->configureRenderables($configuration['renderables']);
        }

        return $configuration;
    }

    /**
     * @param array<int|string, mixed> $renderables
     * @return array<int|string, mixed>
     */
    private function configureRenderables(array $renderables): array
    {
        foreach ($renderables as $key => $renderable) {
            if (!is_array($renderable)) {
                continue;
            }

            if ($this->hasCounter($renderable)) {
                $renderable['renderingOptions']['templateName'] ??= self::TEMPLATE_NAME;
            }

            if (is_array($renderable['renderables'] ?? null)) {
                $renderable['renderables'] = $this->configureRenderables($renderable['renderables']);
            }

            $renderables[$key] = $renderable;
        }

        return $renderables;
    }

    /**
     * @param array<string, mixed> $renderable
     */
    private function hasCounter(array $renderable): bool
    {
        $maxLength = $renderable['properties']['fluidAdditionalAttributes']['maxlength'] ?? 0;

        return ($renderable['type'] ?? '') === self::FORM_ELEMENT_TYPE
            && ($renderable['properties']['dbMapping']['dataType'] ?? '') !== RichTextFormElementConfigurator::DATA_TYPE
            && is_numeric($maxLength)
            && (int)$maxLength > 0;
    }
}
