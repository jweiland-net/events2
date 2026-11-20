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
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Configures the rich text elements of the forms based on EXT:form.
 *
 * An element is a rich text element, if
 * - its identifier is listed in TypoScript "settings.new.richText.elements"
 *   (site setting "events2.form.richText.elements", default:
 *   "detail-description") or
 * - it has "properties.dbMapping.dataType: richText" in the form definition.
 *
 * If the editor is enabled (site setting "events2.form.richText.enable") and
 * EXT:rte_ckeditor is installed, these elements get "dataType: richText" and
 * their mappings in the SaveEventFinisher get "sanitizeRichText". Else they
 * fall back to plain text (dataType "plainText", "convertPlainTextToHtml").
 * Rich text elements are rendered with the partial RichTextEditor/Textarea.html
 * of the partial root path of the form, which loads the assets of the editor.
 * RichTextEditorHook adds the options of the editor while rendering.
 */
readonly class RichTextFormElementConfigurator
{
    public const DATA_TYPE = 'richText';

    private const FALLBACK_DATA_TYPE = 'plainText';

    private const PLAIN_TEXT_OPTIONS = ['convertPlainTextToHtml', 'normalizePlainText'];

    /**
     * Partial of rich text elements in the partial root path of the form.
     * Overridable with "renderingOptions.templateName" of the element.
     */
    private const TEMPLATE_NAME = 'RichTextEditor/Textarea';

    public function __construct(
        private PackageManager $packageManager,
    ) {}

    public function isEnabled(?ServerRequestInterface $request): bool
    {
        return (bool)($this->getSettings($request)['enable'] ?? false)
            && $this->packageManager->isPackageActive('rte_ckeditor');
    }

    /**
     * @return string[]
     */
    public function getConfiguredElementIdentifiers(?ServerRequestInterface $request): array
    {
        return GeneralUtility::trimExplode(',', (string)($this->getSettings($request)['elements'] ?? ''), true);
    }

    /**
     * @param array<string, mixed> $configuration Form definition
     * @return array<string, mixed>
     */
    public function configure(array $configuration, ?ServerRequestInterface $request): array
    {
        $isEnabled = $this->isEnabled($request);
        $richTextIdentifiers = [];

        $configuration['renderables'] = $this->configureRenderables(
            $configuration['renderables'] ?? [],
            $isEnabled ? $this->getConfiguredElementIdentifiers($request) : [],
            $isEnabled ? $this->getTranslationsPath($request) : null,
            $richTextIdentifiers,
        );
        $configuration['finishers'] = $this->configureFinishers(
            $configuration['finishers'] ?? [],
            $richTextIdentifiers,
            $isEnabled,
        );

        return $configuration;
    }

    /**
     * @param array<int|string, mixed> $renderables
     * @param string[] $configuredIdentifiers Identifiers, which become rich text elements
     * @param string|null $translationsPath Folder of the CKEditor translations. NULL, if the editor is disabled.
     * @param string[] $richTextIdentifiers Collects the identifiers of all rich text elements
     * @return array<int|string, mixed>
     */
    private function configureRenderables(
        array $renderables,
        array $configuredIdentifiers,
        ?string $translationsPath,
        array &$richTextIdentifiers,
    ): array {
        foreach ($renderables as $key => $renderable) {
            if (!is_array($renderable)) {
                continue;
            }

            if ($this->isRichTextElement($renderable, $configuredIdentifiers)) {
                $renderable = $this->configureRichTextElement($renderable, $translationsPath);
                $richTextIdentifiers[] = (string)$renderable['identifier'];
            }

            if (is_array($renderable['renderables'] ?? null)) {
                $renderable['renderables'] = $this->configureRenderables(
                    $renderable['renderables'],
                    $configuredIdentifiers,
                    $translationsPath,
                    $richTextIdentifiers,
                );
            }

            $renderables[$key] = $renderable;
        }

        return $renderables;
    }

    /**
     * With the editor the element is rendered with the partial
     * RichTextEditor/Textarea.html, which loads the assets of the editor.
     * Values of the form definition are kept.
     *
     * @param array<string, mixed> $renderable
     * @return array<string, mixed>
     */
    private function configureRichTextElement(array $renderable, ?string $translationsPath): array
    {
        if ($translationsPath === null) {
            $renderable['properties']['dbMapping']['dataType'] = self::FALLBACK_DATA_TYPE;
            return $renderable;
        }

        $renderable['properties']['dbMapping']['dataType'] = self::DATA_TYPE;
        $renderable['renderingOptions']['templateName'] ??= self::TEMPLATE_NAME;
        $renderable['properties']['richTextEditor']['translationsPath'] ??= $translationsPath;

        return $renderable;
    }

    /**
     * @param array<string, mixed> $renderable
     * @param string[] $configuredIdentifiers
     */
    private function isRichTextElement(array $renderable, array $configuredIdentifiers): bool
    {
        return ($renderable['identifier'] ?? '') !== ''
            && (
                in_array($renderable['identifier'], $configuredIdentifiers, true)
                || ($renderable['properties']['dbMapping']['dataType'] ?? '') === self::DATA_TYPE
            );
    }

    /**
     * @param array<int|string, mixed> $finishers
     * @param string[] $richTextIdentifiers
     * @return array<int|string, mixed>
     */
    private function configureFinishers(array $finishers, array $richTextIdentifiers, bool $isEnabled): array
    {
        foreach ($finishers as $key => $finisher) {
            if (!is_array($finisher) || ($finisher['identifier'] ?? '') !== 'SaveEventFinisher') {
                continue;
            }

            $options = $finisher['options'] ?? [];
            $configureOptions = fn(mixed $option): mixed => is_array($option)
                ? $this->configureFinisherOptions($option, $richTextIdentifiers, $isEnabled)
                : $option;
            $finishers[$key]['options'] = isset($options['table']) ? $configureOptions($options) : array_map($configureOptions, $options);
        }

        return $finishers;
    }

    /**
     * @param array<string, mixed> $options
     * @param string[] $richTextIdentifiers
     * @return array<string, mixed>
     */
    private function configureFinisherOptions(array $options, array $richTextIdentifiers, bool $isEnabled): array
    {
        foreach ($richTextIdentifiers as $identifier) {
            if (!is_array($options['elements'][$identifier] ?? null)) {
                continue;
            }

            $options['elements'][$identifier] = $isEnabled
                ? $this->enableSanitizing($options['elements'][$identifier])
                : $this->disableSanitizing($options['elements'][$identifier]);
        }

        return $options;
    }

    /**
     * @param array<string, mixed> $mapping
     * @return array<string, mixed>
     */
    private function enableSanitizing(array $mapping): array
    {
        $mapping = array_diff_key($mapping, array_flip(self::PLAIN_TEXT_OPTIONS));
        $mapping['sanitizeRichText'] = true;

        return $mapping;
    }

    /**
     * Without the editor the textarea contains plain text
     *
     * @param array<string, mixed> $mapping
     * @return array<string, mixed>
     */
    private function disableSanitizing(array $mapping): array
    {
        if (!($mapping['sanitizeRichText'] ?? false)) {
            return $mapping;
        }

        unset($mapping['sanitizeRichText']);
        $mapping['convertPlainTextToHtml'] = true;

        return $mapping;
    }

    /**
     * Folder of the translations of CKEditor (TypoScript
     * "settings.new.richText.translationsPath", site setting
     * "events2.form.richText.translationsPath")
     */
    private function getTranslationsPath(?ServerRequestInterface $request): string
    {
        return (string)($this->getSettings($request)['translationsPath'] ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    private function getSettings(?ServerRequestInterface $request): array
    {
        $frontendTypoScript = $request?->getAttribute('frontend.typoscript');
        if (!$frontendTypoScript instanceof FrontendTypoScript) {
            return [];
        }

        try {
            $setup = $frontendTypoScript->getSetupArray();
        } catch (\RuntimeException) {
            // The setup is not available in cached frontend scope
            return [];
        }

        $settings = $setup['plugin.']['tx_events2.']['settings.']['new.']['richText.'] ?? [];

        return is_array($settings) ? $settings : [];
    }
}
