<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Utility;

final class YouTubeUrlUtility
{
    private const URL_PATTERN = '~^(?:https?:)?//(?:www\.)?youtu(?:be\.com|\.be)/(?:watch\?v=|embed/|live/|v/|shorts/|)?([a-zA-Z0-9_-]{11})(?:[?&].*)?$~i';

    public static function isValid(string $url): bool
    {
        return self::extractVideoId($url) !== null;
    }

    public static function extractVideoId(string $url): ?string
    {
        if (preg_match(self::URL_PATTERN, trim($url), $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }
}

