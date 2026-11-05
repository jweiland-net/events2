<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Form;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Switches the description field of the form based on EXT:form to the
 * rich text editor, if the TypoScript setting
 * plugin.tx_events2.settings.new.richTextDescription (site setting
 * events2.form.richTextDescription) is enabled and EXT:rte_ckeditor is
 * installed. The element is found by its identifier, so overridden form
 * definitions keep working as long as they use the same identifier.
 */
readonly class RichTextDescriptionConfigurator
{
    public const ELEMENT_IDENTIFIER = 'detail-description';

    public const DATA_ATTRIBUTE = 'data-events2-rich-text';

    public function __construct(
        private RichTextEditorAssets $richTextEditorAssets,
    ) {}

    public function isEnabled(?ServerRequestInterface $request): bool
    {
        $frontendTypoScript = $request?->getAttribute('frontend.typoscript');
        if (!$frontendTypoScript instanceof FrontendTypoScript || !$frontendTypoScript->hasSetup()) {
            return false;
        }

        $settings = $frontendTypoScript->getSetupArray()['plugin.']['tx_events2.']['settings.'] ?? [];

        return (bool)($settings['new.']['richTextDescription'] ?? false)
            && ExtensionManagementUtility::isLoaded('rte_ckeditor');
    }

    /**
     * @param array<string, mixed> $configuration Form definition
     * @return array<string, mixed>
     */
    public function configure(array $configuration, ServerRequestInterface $request): array
    {
        $attributeValue = json_encode($this->richTextEditorAssets->getEditorOptions($request), JSON_THROW_ON_ERROR);
        $configuration['renderables'] = $this->configureRenderables($configuration['renderables'] ?? [], $attributeValue);
        $configuration['finishers'] = $this->configureFinishers($configuration['finishers'] ?? []);
        $this->richTextEditorAssets->load();

        return $configuration;
    }

    /**
     * @param array<int|string, mixed> $renderables
     * @return array<int|string, mixed>
     */
    private function configureRenderables(array $renderables, string $attributeValue): array
    {
        foreach ($renderables as $key => $renderable) {
            if (!is_array($renderable)) {
                continue;
            }

            if (($renderable['identifier'] ?? '') === self::ELEMENT_IDENTIFIER) {
                $renderable['properties']['dbMapping']['dataType'] = 'richText';
                $renderable['properties']['fluidAdditionalAttributes'][self::DATA_ATTRIBUTE] = $attributeValue;
            }

            if (is_array($renderable['renderables'] ?? null)) {
                $renderable['renderables'] = $this->configureRenderables($renderable['renderables'], $attributeValue);
            }

            $renderables[$key] = $renderable;
        }

        return $renderables;
    }

    /**
     * @param array<int|string, mixed> $finishers
     * @return array<int|string, mixed>
     */
    private function configureFinishers(array $finishers): array
    {
        foreach ($finishers as $key => $finisher) {
            if (!is_array($finisher) || ($finisher['identifier'] ?? '') !== 'SaveEventFinisher') {
                continue;
            }

            $options = $finisher['options'] ?? [];
            $finishers[$key]['options'] = isset($options['table'])
                ? $this->configureFinisherOptions($options)
                : array_map(fn(mixed $option): mixed => is_array($option) ? $this->configureFinisherOptions($option) : $option, $options);
        }

        return $finishers;
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function configureFinisherOptions(array $options): array
    {
        if (isset($options['elements'][self::ELEMENT_IDENTIFIER])) {
            unset($options['elements'][self::ELEMENT_IDENTIFIER]['convertPlainTextToHtml']);
            $options['elements'][self::ELEMENT_IDENTIFIER]['sanitizeRichText'] = true;
        }

        return $options;
    }
}
