<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * This VH checks if given URI is a valid YouTube-Link
 * and returns a special YouTube embed URI.
 */
final class CreateYoutubeUriViewHelper extends AbstractViewHelper
{
    private const PATTERN = '~^(|http:|https:)//(|www.)youtu(\.be|be)(.*?)(v=|embed/|)([a-zA-Z0-9_-]+)$~i';

    public function initializeArguments(): void
    {
        $this->registerArgument('link', 'string', 'Insert the YouTube-Link', true);
    }

    /**
     * Extract the YouTube ID from link and return the embedded YouTube url.
     */
    public function render(): string
    {
        if (preg_match(self::PATTERN, $this->arguments['link'], $matches)) {
            return '//www.youtube.com/embed/' . $matches[6];
        }

        return '//www.youtube.com/embed/' . $this->arguments['link'];
    }
}
