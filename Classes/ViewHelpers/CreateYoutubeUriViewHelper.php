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
 * This VH checks if the given URI is a valid YouTube-Link
 * and returns a special YouTube embed URI.
 */
final class CreateYoutubeUriViewHelper extends AbstractViewHelper
{
    private const YOUTUBE_PATTERN = '~^(?:https?:)?//(?:www\.)?youtu(?:be\.com|\.be)/(?:watch\?v=|embed/|live/|v/|shorts/|)?([a-zA-Z0-9_-]{11})(?:[?&].*)?$~i';

    public function initializeArguments(): void
    {
        $this->registerArgument('link', 'string', 'Insert the YouTube-Link', true);
    }

    /**
     * Extracts the YouTube ID from a link and returns the embedded YouTube URL.
     * * @return string The formatted embed URL
     */
    public function render(): string
    {
        $subject = trim($this->arguments['link']);

        if (preg_match(self::YOUTUBE_PATTERN, $subject, $matches)) {
            // Since all preceding groups are non-capturing (?:), the ID is always at index 1.
            return '//www.youtube.com/embed/' . $matches[1];
        }

        // Fallback: Assume the input is a raw ID if regex doesn't match
        return '//www.youtube.com/embed/' . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
    }}
