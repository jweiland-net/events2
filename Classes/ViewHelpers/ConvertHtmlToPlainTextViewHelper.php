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
 * There is no RTE for teaser/detailInformation in frontend, so any markup in there either comes
 * from a backend editor using the RTE, or from SaveEventFinisher::convertPlainTextToHtml(), which
 * only ever produces <p> and <br> tags. This ViewHelper turns </p> and <br> back into real line
 * breaks, strips every other tag along with its markup (keeping its text content), and escapes the
 * result. Chain it with f:format.nl2br to get visible line breaks again.
 */
final class ConvertHtmlToPlainTextViewHelper extends AbstractViewHelper
{
    /**
     * No output escaping, as we build our own htmlspecialchars-escaped text.
     */
    protected $escapeOutput = false;

    /**
     * The value must reach render() unescaped, else strip_tags() below has
     * nothing left to strip.
     */
    protected $escapeChildren = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('value', 'string', 'The HTML formatted value to convert to plain text', false, null);
    }

    public function render(): string
    {
        $value = $this->arguments['value'] ?? $this->renderChildren();
        if (!is_string($value) || trim($value) === '') {
            return '';
        }

        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = (string)preg_replace('/<\/p\s*>/i', "\n\n", $value);
        $value = (string)preg_replace('/<br\s*\/?>/i', "\n", $value);
        $value = strip_tags($value);

        // RTE editors leave a raw newline between tags in the stored HTML. Collapse any run of
        // blank lines, whether it came from that raw whitespace or from a genuine paragraph break
        // above, into a single blank line.
        $value = (string)preg_replace('/\n[ \t]*\n+/', "\n\n", $value);

        return htmlspecialchars(trim($value), ENT_QUOTES | ENT_HTML5);
    }
}
