<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Traits;

use JWeiland\Events2\Service\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Trait to inject TypoScriptService. Mostly used in controllers.
 */
trait InjectTypoScriptServiceTrait
{
    protected TypoScriptService $typoScriptService;

    public function injectTypoScriptService(TypoScriptService $typoScriptService): void
    {
        $this->typoScriptService = $typoScriptService;
    }

    /**
     * As we don't have access to the loading order of inject methods it may happen that
     * TypoScriptService is not loaded within other inject methods like injectConfigurationManager of
     * AbstractController.
     * Error: Do not call $this->typoScriptService before initialization.
     */
    public function getTypoScriptService(): TypoScriptService
    {
        return GeneralUtility::makeInstance(TypoScriptService::class);
    }
}
