<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * This VH checks if given URI is a valid YouTube-Link
 * and returns a special YouTube embed URI.
 */
final class CreateYoutubeUriViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    public function initializeArguments(): void
    {
        $this->registerArgument('link', 'string', 'Insert the YouTube-Link', true);
    }

    /**
     * Extract the YouTube ID from link and return the embedded YouTube url.
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext,
    ): string {
        if (preg_match('~^(|http:|https:)//(|www.)youtu(\.be|be)(.*?)(v=|embed/|)([a-zA-Z0-9_-]+)$~i', $arguments['link'], $matches)) {
            return '//www.youtube.com/embed/' . $matches[6];
        }

        return '//www.youtube.com/embed/' . $arguments['link'];
    }
}
