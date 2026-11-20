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
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Builds the options of the CKEditor 5 of a rich text element, which are
 * added as JSON to the textarea. The JavaScript module and the style sheets
 * are loaded by the partial RichTextEditor/LoadAssets.html.
 */
readonly class RichTextEditorOptions
{
    /**
     * The headings and toolbar items of the editor are based on the allowed tags.
     *
     * @param string $translationsPath Folder of the translations of CKEditor, e.g. "EXT:rte_ckeditor/Resources/Public/Contrib/translations/"
     * @return array<string, mixed>
     */
    public function getEditorOptions(
        ServerRequestInterface $request,
        RichTextSanitizerConfiguration $configuration,
        string $translationsPath,
    ): array {
        $languageCode = $this->getLanguageCode($request);

        return [
            'language' => $languageCode,
            'allowedTags' => $configuration->allowedTags,
            'translationUrl' => $this->getTranslationUrl($languageCode, $translationsPath),
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
    private function getTranslationUrl(string $languageCode, string $translationsPath): string
    {
        if ($languageCode === 'en' || trim($translationsPath) === '') {
            return '';
        }

        $translationFile = rtrim(trim($translationsPath), '/') . '/' . $languageCode . '.js';
        if (!is_file(GeneralUtility::getFileAbsFileName($translationFile))) {
            return '';
        }

        return PathUtility::getPublicResourceWebPath($translationFile);
    }
}
