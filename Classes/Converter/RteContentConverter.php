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

    private const LINE_BREAK = "\r\n";

    private const NON_BREAKING_SPACE = "\u{00A0}";

    /**
     * Matches a run of lines, which contain nothing but spaces, tabs and
     * non-breaking spaces, including the line break before them.
     */
    private const BLANK_LINES_PATTERN = '/\n(?:[ \t\x{00A0}]*\n)+/u';

    /**
     * A single line break is meant as <br />, an empty line between two blocks
     * of text is meant as a new paragraph. Any tag the visitor might have
     * typed or pasted in is removed beforehand, so only our own <p> and <br />
     * tags end up in the database. Lines containing non-breaking spaces only
     * count as empty lines. Other non-breaking spaces are stored as "&nbsp;"
     * like the backend RTE does.
     */
    public function convertPlainTextToHtml(string $plainText): string
    {
        // Invalid UTF-8 would let the unicode patterns below fail
        $plainText = str_replace(["\r\n", "\r"], "\n", strip_tags(mb_scrub($plainText, 'UTF-8')));
        $paragraphs = array_filter(
            array_map(trim(...), preg_split(self::BLANK_LINES_PATTERN, $plainText) ?: []),
            fn(string $paragraph): bool => !$this->isBlank($paragraph),
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
     *
     * Lines containing nothing but whitespace or non-breaking spaces (e.g.
     * "<p>&nbsp;</p>") are removed. Other non-breaking spaces are kept as
     * U+00A0, so e.g. "10&nbsp;Uhr" does not wrap. Set $keepNonBreakingSpaces
     * to false to get normal spaces instead, e.g. for structured data.
     */
    public function convertHtmlToPlainText(string $html, bool $keepNonBreakingSpaces = true): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", mb_scrub($html, 'UTF-8'));
        // Whitespace around these tags is markup only, so it is consumed together with the tag
        $value = (string)preg_replace('/\s*<\/p\s*>\s*/i', "\n\n", $value);
        $value = (string)preg_replace('/[ \t]*<br\s*\/?>[ \t]*\n?/i', "\n", $value);
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (!$keepNonBreakingSpaces) {
            $value = str_replace(self::NON_BREAKING_SPACE, ' ', $value);
        }

        // Collapse any run of blank lines into a single blank line
        $value = (string)preg_replace(self::BLANK_LINES_PATTERN, "\n\n", "\n" . $value . "\n");

        // Non-breaking spaces at the beginning and end of the text are kept, as they are content
        return $this->isBlank($value) ? '' : trim($value, " \t\n");
    }

    /**
     * Normalizes plain text for plain text columns like "teaser" the way the
     * backend stores the value of a textarea: tags are removed, line breaks
     * are CRLF (as sent by browsers) and the value is trimmed. The value is
     * NOT HTML encoded, as it is plain text. Templates have to escape it.
     */
    public function normalizePlainText(string $plainText): string
    {
        $plainText = str_replace(["\r\n", "\r"], "\n", strip_tags(mb_scrub($plainText, 'UTF-8')));

        return str_replace("\n", self::LINE_BREAK, trim($plainText));
    }

    private function convertLinesToHtml(string $paragraph): string
    {
        $lines = array_map(
            static fn(string $line): string => str_replace(
                self::NON_BREAKING_SPACE,
                '&nbsp;',
                htmlspecialchars(trim($line), ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ),
            explode("\n", $paragraph),
        );

        return implode('<br />', $lines);
    }

    /**
     * Returns true, if the value contains nothing but whitespace and non-breaking spaces
     */
    private function isBlank(string $value): bool
    {
        return preg_match('/^[\s\x{00A0}]*$/u', $value) === 1;
    }
}
