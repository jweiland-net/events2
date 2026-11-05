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
     * Characters of the private use area of unicode. They mark the borders of
     * block elements until runs of them are converted into line breaks.
     */
    private const LINE_MARKER = "\u{E000}";

    private const PARAGRAPH_MARKER = "\u{E001}";

    /**
     * Start and end of these elements are separated by an empty line
     */
    private const PARAGRAPH_TAGS = [
        'address', 'article', 'aside', 'blockquote', 'div', 'dl', 'figcaption', 'figure', 'footer',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'header', 'hr', 'main', 'nav', 'p', 'pre', 'section', 'table',
    ];

    /**
     * Start and end of these elements are separated by a line break. So list
     * items and nested lists are on their own line without empty lines.
     */
    private const LINE_TAGS = [
        'caption', 'dd', 'dt', 'li', 'ol', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr', 'ul',
    ];

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
     * plain text. Paragraphs, headings and other blocks are separated by an
     * empty line, list items (also of nested lists) by a line break, <br />
     * becomes a line break and entities are decoded. Every other tag is
     * stripped while its text content is kept. The result is NOT escaped.
     *
     * Lines containing nothing but whitespace or non-breaking spaces (e.g.
     * "<p>&nbsp;</p>") are removed. Other non-breaking spaces are kept as
     * U+00A0, so e.g. "10&nbsp;Uhr" does not wrap. Set $keepNonBreakingSpaces
     * to false to get normal spaces instead, e.g. for structured data.
     */
    public function convertHtmlToPlainText(string $html, bool $keepNonBreakingSpaces = true): string
    {
        $value = str_replace(
            ["\r\n", "\r", self::LINE_MARKER, self::PARAGRAPH_MARKER],
            ["\n", "\n", '', ''],
            mb_scrub($html, 'UTF-8'),
        );
        $value = (string)preg_replace('/[ \t]*<br\s*\/?>[ \t]*\n?/i', "\n", $value);
        $value = $this->convertBlockTagsToLineBreaks($value);
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

    /**
     * Whitespace around block tags is markup only, so it is consumed together
     * with the tag. A run of markers between two texts becomes one line break,
     * or one empty line, if a paragraph like element starts or ends there.
     */
    private function convertBlockTagsToLineBreaks(string $value): string
    {
        $value = (string)preg_replace($this->getBlockTagPattern(self::PARAGRAPH_TAGS), self::PARAGRAPH_MARKER, $value);
        $value = (string)preg_replace($this->getBlockTagPattern(self::LINE_TAGS), self::LINE_MARKER, $value);

        return (string)preg_replace_callback(
            '/[' . self::LINE_MARKER . self::PARAGRAPH_MARKER . ']+/u',
            static fn(array $markers): string => str_contains($markers[0], self::PARAGRAPH_MARKER) ? "\n\n" : "\n",
            $value,
        );
    }

    /**
     * Matches start and end tags of the given elements incl. attributes
     *
     * @param string[] $tagNames
     */
    private function getBlockTagPattern(array $tagNames): string
    {
        return '/\s*<\/?(?:' . implode('|', $tagNames) . ')(?:\s[^>]*)?\/?>\s*/iu';
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
