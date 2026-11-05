<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Converter;

use JWeiland\Events2\Converter\RteContentConverter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case.
 */
class RteContentConverterTest extends UnitTestCase
{
    /**
     * Plain text as typed into the textarea of the frontend form
     */
    private const PLAIN_TEXT = "Erste Zeile der Beschreibung.\n"
        . "Zweite Zeile direkt darunter.\n"
        . "\n"
        . "Neuer Absatz nach einer Leerzeile.\n"
        . 'Letzte Zeile mit Umlauten: äöü ß & "Anführungszeichen".';

    /**
     * Same content saved once in the backend with CKEditor and RteHtmlParser
     */
    private const BACKEND_RTE_HTML = '<p>Erste Zeile der Beschreibung.<br />Zweite Zeile direkt darunter.</p>'
        . "\r\n"
        . '<p>Neuer Absatz nach einer Leerzeile.<br />Letzte Zeile mit Umlauten: äöü ß &amp; "Anführungszeichen".</p>';

    /**
     * Format of SaveEventFinisher before events2 11.1.1
     */
    private const LEGACY_FRONTEND_HTML = "<p>Erste Zeile der Beschreibung.<br />\nZweite Zeile direkt darunter.</p>"
        . "<p>Neuer Absatz nach einer Leerzeile.<br />\n"
        . 'Letzte Zeile mit Umlauten: äöü ß &amp; &quot;Anführungszeichen&quot;.</p>';

    private RteContentConverter $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new RteContentConverter();
    }

    #[Test]
    public function convertPlainTextToHtmlReturnsFormatOfBackendRte(): void
    {
        self::assertSame(
            self::BACKEND_RTE_HTML,
            $this->subject->convertPlainTextToHtml(self::PLAIN_TEXT),
        );
    }

    #[Test]
    public function convertPlainTextToHtmlWithWindowsLineEndingsReturnsFormatOfBackendRte(): void
    {
        self::assertSame(
            self::BACKEND_RTE_HTML,
            $this->subject->convertPlainTextToHtml(str_replace("\n", "\r\n", self::PLAIN_TEXT)),
        );
    }

    #[Test]
    public function convertPlainTextToHtmlWithOldMacLineEndingsReturnsFormatOfBackendRte(): void
    {
        self::assertSame(
            self::BACKEND_RTE_HTML,
            $this->subject->convertPlainTextToHtml(str_replace("\n", "\r", self::PLAIN_TEXT)),
        );
    }

    public static function plainTextDataProvider(): array
    {
        return [
            'empty string' => [
                '',
                '',
            ],
            'whitespace only' => [
                " \r\n \n\t",
                '',
            ],
            'single line' => [
                'Hello world',
                '<p>Hello world</p>',
            ],
            'single line break becomes br without newline' => [
                "Hello\nworld",
                '<p>Hello<br />world</p>',
            ],
            'empty line starts new paragraph separated by CRLF' => [
                "First paragraph\n\nSecond paragraph",
                "<p>First paragraph</p>\r\n<p>Second paragraph</p>",
            ],
            'multiple empty lines start one new paragraph' => [
                "First paragraph\n\n\n\nSecond paragraph",
                "<p>First paragraph</p>\r\n<p>Second paragraph</p>",
            ],
            'line with whitespace only starts new paragraph' => [
                "First paragraph\n \t\nSecond paragraph",
                "<p>First paragraph</p>\r\n<p>Second paragraph</p>",
            ],
            'surrounding whitespace is trimmed' => [
                "\n\n  Hello  \n  world  \n\n",
                '<p>Hello<br />world</p>',
            ],
            'tags typed by visitor are stripped' => [
                '<script>alert("xss")</script><b>bold text</b>',
                '<p>alert("xss")bold text</p>',
            ],
            'only ampersand and angle brackets are encoded' => [
                'Tom & Jerry "quoted" \'single\' a < b > c',
                '<p>Tom &amp; Jerry "quoted" \'single\' a &lt; b &gt; c</p>',
            ],
            'umlauts stay raw' => [
                'äöü ÄÖÜ ß €',
                '<p>äöü ÄÖÜ ß €</p>',
            ],
            'non-breaking space is stored as entity like the backend RTE' => [
                "Beginn 10\u{00A0}Uhr",
                '<p>Beginn 10&nbsp;Uhr</p>',
            ],
            'non-breaking space at end of line is kept' => [
                "Ende.\u{00A0}\nNeue Zeile",
                '<p>Ende.&nbsp;<br />Neue Zeile</p>',
            ],
            'line with non-breaking spaces only starts new paragraph' => [
                "First paragraph\n\u{00A0} \u{00A0}\nSecond paragraph",
                "<p>First paragraph</p>\r\n<p>Second paragraph</p>",
            ],
            'text with non-breaking spaces only returns empty string' => [
                "\u{00A0}\n\u{00A0}",
                '',
            ],
            'invalid UTF-8 is substituted instead of dropping the text' => [
                "Hello \xFF world",
                '<p>Hello ? world</p>',
            ],
        ];
    }

    #[Test]
    #[DataProvider('plainTextDataProvider')]
    public function convertPlainTextToHtmlReturnsExpectedHtml(string $plainText, string $expectedHtml): void
    {
        self::assertSame(
            $expectedHtml,
            $this->subject->convertPlainTextToHtml($plainText),
        );
    }

    public static function htmlDataProvider(): array
    {
        return [
            'backend RTE format' => [
                self::BACKEND_RTE_HTML,
            ],
            'legacy frontend format' => [
                self::LEGACY_FRONTEND_HTML,
            ],
            'backend RTE format with LF between paragraphs' => [
                str_replace("\r\n", "\n", self::BACKEND_RTE_HTML),
            ],
            'backend RTE format with br and newline' => [
                str_replace('<br />', "<br>\r\n", self::BACKEND_RTE_HTML),
            ],
        ];
    }

    #[Test]
    #[DataProvider('htmlDataProvider')]
    public function convertHtmlToPlainTextReturnsOriginalPlainText(string $html): void
    {
        self::assertSame(
            self::PLAIN_TEXT,
            $this->subject->convertHtmlToPlainText($html),
        );
    }

    public static function htmlToPlainTextDataProvider(): array
    {
        return [
            'empty string' => [
                '',
                '',
            ],
            'plain text without tags keeps line breaks' => [
                "Hello\nworld",
                "Hello\nworld",
            ],
            'plain text with CRLF gets LF' => [
                "Hello\r\nworld",
                "Hello\nworld",
            ],
            'other tags are stripped, text survives' => [
                '<p><b>Bold</b> and <a href="https://example.com">a link</a></p>',
                'Bold and a link',
            ],
            'entities are decoded' => [
                '<p>&lt;b&gt; &amp; &quot;x&quot; &#039;y&#039; &auml;</p>',
                '<b> & "x" \'y\' ä',
            ],
            'empty paragraphs do not create double blank lines' => [
                "<p>First</p>\r\n<p>&nbsp;</p>\r\n<p></p>\r\n<p>Second</p>",
                "First\n\nSecond",
            ],
            'inline non-breaking space is kept as U+00A0' => [
                '<p>Beginn 10&nbsp;Uhr</p>',
                "Beginn 10\u{00A0}Uhr",
            ],
            'paragraph with non-breaking space only is removed' => [
                "<p>Beginn 10&nbsp;Uhr</p>\r\n<p>&nbsp;</p>\r\n<p>Zweiter Absatz</p>",
                "Beginn 10\u{00A0}Uhr\n\nZweiter Absatz",
            ],
            'leading and trailing paragraphs with non-breaking spaces only are removed' => [
                "<p>&nbsp;</p>\r\n<p>Text</p>\r\n<p>&nbsp; &nbsp;</p>",
                'Text',
            ],
            'line with non-breaking space only becomes a blank line' => [
                '<p>a<br />&nbsp;<br />b</p>',
                "a\n\nb",
            ],
            'leading whitespace of a line after a blank line is kept' => [
                "<p>a</p>\r\n<p>&nbsp; b</p>",
                "a\n\n\u{00A0} b",
            ],
            'content with non-breaking spaces only returns empty string' => [
                '<p>&nbsp;</p>',
                '',
            ],
            'no stray carriage return' => [
                "<p>First<br />\r\nline</p>\r\n\r\n<p>Second</p>\r\n",
                "First\nline\n\nSecond",
            ],
        ];
    }

    #[Test]
    #[DataProvider('htmlToPlainTextDataProvider')]
    public function convertHtmlToPlainTextReturnsExpectedPlainText(string $html, string $expectedPlainText): void
    {
        self::assertSame(
            $expectedPlainText,
            $this->subject->convertHtmlToPlainText($html),
        );
    }

    public static function normalizePlainTextDataProvider(): array
    {
        return [
            'empty string' => [
                '',
                '',
            ],
            'surrounding whitespace is trimmed' => [
                "  \r\n Kurzbeschreibung \n ",
                'Kurzbeschreibung',
            ],
            'LF becomes CRLF like a textarea submitted by the browser' => [
                "Zeile 1\nZeile 2\n\nZeile 4",
                "Zeile 1\r\nZeile 2\r\n\r\nZeile 4",
            ],
            'CRLF stays CRLF' => [
                "Zeile 1\r\nZeile 2",
                "Zeile 1\r\nZeile 2",
            ],
            'CR becomes CRLF' => [
                "Zeile 1\rZeile 2",
                "Zeile 1\r\nZeile 2",
            ],
            'tags are stripped' => [
                '<p>Kurz <b>fett</b></p><script>alert(1)</script>',
                'Kurz fettalert(1)',
            ],
            'special characters are not encoded' => [
                'Tom & Jerry "quoted" a < b > c äöü ß',
                'Tom & Jerry "quoted" a < b > c äöü ß',
            ],
            'non-breaking space stays raw' => [
                "10\u{00A0}Uhr",
                "10\u{00A0}Uhr",
            ],
        ];
    }

    #[Test]
    #[DataProvider('normalizePlainTextDataProvider')]
    public function normalizePlainTextReturnsPlainTextLikeBackendTextarea(string $plainText, string $expected): void
    {
        self::assertSame(
            $expected,
            $this->subject->normalizePlainText($plainText),
        );
    }

    #[Test]
    public function convertHtmlToPlainTextWithoutNonBreakingSpacesReturnsNormalSpaces(): void
    {
        self::assertSame(
            "Beginn 10 Uhr\n\nZweiter Absatz",
            $this->subject->convertHtmlToPlainText(
                "<p>Beginn 10&nbsp;Uhr</p>\r\n<p>&nbsp;</p>\r\n<p>Zweiter Absatz</p>",
                false,
            ),
        );
    }

    #[Test]
    public function roundTripKeepsNonBreakingSpaceOfBackendRte(): void
    {
        $html = "<p>Beginn 10&nbsp;Uhr<br />Einlass 9&nbsp;Uhr</p>\r\n<p>Zweiter Absatz.&nbsp;</p>";

        self::assertSame(
            $html,
            $this->subject->convertPlainTextToHtml($this->subject->convertHtmlToPlainText($html)),
        );
    }

    #[Test]
    #[DataProvider('htmlDataProvider')]
    public function roundTripOfStoredHtmlReturnsBackendRteFormat(string $html): void
    {
        self::assertSame(
            self::BACKEND_RTE_HTML,
            $this->subject->convertPlainTextToHtml($this->subject->convertHtmlToPlainText($html)),
        );
    }
}
