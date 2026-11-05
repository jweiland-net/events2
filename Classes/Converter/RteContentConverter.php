<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Converter;

/**
 * There is no RTE for teaser and detail information in frontend. This class
 * converts the plain text of the frontend form into the same HTML the backend
 * RTE (CKEditor and RteHtmlParser) stores and back into plain text.
 *
 * Backend RTE format: "<p>Line 1<br />Line 2</p>\r\n<p>Paragraph 2</p>"
 * Only "&", "<" and ">" are encoded. Quotes stay raw.
 */
readonly class RteContentConverter
{
    private const PARAGRAPH_SEPARATOR = "\r\n";

    /**
     * A single line break is meant as <br />, an empty line between two blocks
     * of text is meant as a new paragraph. Any tag the visitor might have
     * typed or pasted in is removed beforehand, so only our own <p> and <br />
     * tags end up in the database.
     */
    public function convertPlainTextToHtml(string $plainText): string
    {
        $plainText = str_replace(["\r\n", "\r"], "\n", strip_tags($plainText));

        $paragraphs = array_filter(
            array_map(trim(...), preg_split('/\n[ \t]*\n/', trim($plainText)) ?: []),
            static fn(string $paragraph): bool => $paragraph !== '',
        );

        return implode(self::PARAGRAPH_SEPARATOR, array_map(
            fn(string $paragraph): string => '<p>' . $this->convertLinesToHtml($paragraph) . '</p>',
            $paragraphs,
        ));
    }

    /**
     * Converts HTML of the backend RTE or of convertPlainTextToHtml() (also the
     * format of events2 < 11.1.1 with a raw newline after each <br />) into
     * plain text. Paragraphs are separated by an empty line, <br /> becomes a
     * line break and entities are decoded. Every other tag is stripped while
     * its text content is kept. The result is NOT escaped.
     */
    public function convertHtmlToPlainText(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $value = str_replace(["\r\n", "\r"], "\n", $html);
        // Whitespace around these tags is markup only, so it is consumed together with the tag
        $value = (string)preg_replace('/\s*<\/p\s*>\s*/i', "\n\n", $value);
        $value = (string)preg_replace('/[ \t]*<br\s*\/?>[ \t]*\n?/i', "\n", $value);
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // The RTE stores empty paragraphs as "<p>&nbsp;</p>"
        $value = str_replace("\u{00A0}", ' ', $value);

        // Collapse any run of blank lines into a single blank line
        $value = (string)preg_replace('/\n[ \t]*(?:\n[ \t]*)+/', "\n\n", $value);

        return trim($value);
    }

    private function convertLinesToHtml(string $paragraph): string
    {
        $lines = array_map(
            static fn(string $line): string => htmlspecialchars(trim($line), ENT_NOQUOTES, 'UTF-8'),
            explode("\n", $paragraph),
        );

        return implode('<br />', $lines);
    }
}
