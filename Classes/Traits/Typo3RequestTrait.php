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
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Trait to handle $_GET, $_POST and request attributes
 */
trait Typo3RequestTrait
{
    protected function getTypoScriptFrontendController(
        ?ServerRequestInterface $request = null,
    ): TypoScriptFrontendController {
        $request ??= $this->getTypo3Request();

        return $request->getAttribute('frontend.controller');
    }

    protected function getPostFromRequest(?ServerRequestInterface $request = null): array
    {
        $request ??= $this->getTypo3Request();

        return is_array($request->getParsedBody()) ? $request->getParsedBody() : [];
    }

    protected function getGetFromRequest(?ServerRequestInterface $request = null): array
    {
        $request ??= $this->getTypo3Request();

        return $request->getQueryParams();
    }

    /**
     * Merge given argument with value from GET with value from POST.
     * Replacement for old GeneralUtility::_GPmerged
     */
    protected function getMergedWithPostFromRequest(
        string $argument,
        ?ServerRequestInterface $request = null,
    ): array {
        $request ??= $this->getTypo3Request();

        $getMergedWithPost = $request->getQueryParams()[$argument] ?? [];
        ArrayUtility::mergeRecursiveWithOverrule(
            $getMergedWithPost,
            ($request->getParsedBody()[$argument] ?? []),
        );

        return $getMergedWithPost;
    }

    private function isFrontendRequest(?ServerRequestInterface $request = null): bool
    {
        $request ??= $this->getTypo3Request();

        return ApplicationType::fromRequest($request)->isFrontend();
    }

    protected function getTypo3Request(): ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'];
    }
}
