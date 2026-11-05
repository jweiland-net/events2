<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Form;

use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Loads the counter of remaining letters for textareas with a maxlength
 * in the form based on EXT:form and provides its translated label.
 */
readonly class RemainingLettersAssets
{
    public const JAVASCRIPT_MODULE = '@jweiland/events2/remaining-letters.js';

    private const LABEL_KEY = 'remainingLetters';

    /**
     * "%s" is replaced with the amount of remaining letters by JavaScript
     */
    private const DEFAULT_LABEL = '%s characters left';

    public function __construct(
        private AssetCollector $assetCollector,
    ) {}

    public function load(): void
    {
        $this->assetCollector->addJavaScriptModule(self::JAVASCRIPT_MODULE);
    }

    public function getLabel(): string
    {
        return LocalizationUtility::translate(self::LABEL_KEY, 'events2') ?? self::DEFAULT_LABEL;
    }
}
