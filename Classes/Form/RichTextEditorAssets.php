<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Form;

use JWeiland\Events2\Sanitizer\RichTextSanitizerConfiguration;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Loads the CKEditor 5 of the frontend form. The ES modules, translations
 * and styles shipped with EXT:rte_ckeditor are used. No build step needed.
 * The import map of the modules is defined in Configuration/JavaScriptModules.php
 * and rendered by the PageRenderer of TYPO3.
 */
readonly class RichTextEditorAssets
{
    public const JAVASCRIPT_MODULE = '@jweiland/events2/rich-text-editor.js';

    private const TRANSLATIONS_PATH = 'EXT:rte_ckeditor/Resources/Public/Contrib/translations/';

    private const STYLE_SHEETS = [
        'events2-ckeditor' => 'EXT:rte_ckeditor/Resources/Public/Css/editor.css',
        'events2-rich-text-editor' => 'EXT:events2/Resources/Public/Css/RichTextEditor.css',
    ];

    public function __construct(
        private AssetCollector $assetCollector,
    ) {}

    public function load(): void
    {
        $this->assetCollector->addJavaScriptModule(self::JAVASCRIPT_MODULE);
        foreach (self::STYLE_SHEETS as $identifier => $source) {
            $this->assetCollector->addStyleSheet($identifier, $source);
        }
    }

    /**
     * Options for the JavaScript module, which are added as JSON to the textarea.
     * The headings and toolbar items of the editor are based on the allowed tags.
     *
     * @return array<string, mixed>
     */
    public function getEditorOptions(ServerRequestInterface $request, RichTextSanitizerConfiguration $configuration): array
    {
        $languageCode = $this->getLanguageCode($request);

        return [
            'language' => $languageCode,
            'allowedTags' => $configuration->allowedTags,
            'translationUrl' => $this->getTranslationUrl($languageCode),
            'labels' => [
                'openInNewTab' => LocalizationUtility::translate('richText.openInNewTab', 'events2') ?? 'Open in a new tab',
            ],
        ];
    }

    private function getLanguageCode(ServerRequestInterface $request): string
    {
        $siteLanguage = $request->getAttribute('language');
        $languageCode = $siteLanguage instanceof SiteLanguage ? $siteLanguage->getLocale()->getLanguageCode() : 'en';

        return preg_match('/^[a-z]{2,3}$/', $languageCode) === 1 ? $languageCode : 'en';
    }

    /**
     * English is the built-in language of CKEditor and has no translation file
     */
    private function getTranslationUrl(string $languageCode): string
    {
        $translationFile = self::TRANSLATIONS_PATH . $languageCode . '.js';
        if ($languageCode === 'en' || !is_file(GeneralUtility::getFileAbsFileName($translationFile))) {
            return '';
        }

        return PathUtility::getPublicResourceWebPath($translationFile);
    }
}
