<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Traits;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/*
 * Trait to get TypoScriptFrontendController
 */
trait TypoScriptFrontendControllerTrait
{
    protected function getTypoScriptFrontendController(?ServerRequestInterface $request = null): TypoScriptFrontendController
    {
        $request ??= $this->getTypo3Request();

        return $request->getAttribute('frontend.controller');
    }

    protected function getTypo3Request(): ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'];
    }
}
