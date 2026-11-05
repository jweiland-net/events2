<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Sanitizer;

use JWeiland\Events2\Sanitizer\RichTextSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case.
 */
class RichTextSanitizerTest extends UnitTestCase
{
    private const ALLOWED_T3_HREFS = [
        't3://file?uid=1234',
        't3://page?uid=12&type=0',
    ];

    private RichTextSanitizer $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new RichTextSanitizer();
    }

    public static function allowedHtmlDataProvider(): array
    {
        return [
            'paragraphs with line breaks' => ['<p>a<br>b</p><p>c</p>'],
            'strong and em' => ['<p><strong>a</strong> <em>b</em></p>'],
            'headings h2 and h3' => ['<h2>a</h2><h3>b</h3>'],
            'nested lists' => ['<ul><li>a<ol><li>b</li><li>c</li></ol></li><li>d</li></ul>'],
            'http link' => ['<p><a href="http://example.com">a</a></p>'],
            'https link with query' => ['<p><a href="https://example.com/?a=1&amp;b=2">a</a></p>'],
            'mailto link' => ['<p><a href="mailto:info@example.com">a</a></p>'],
            'tel link' => ['<p><a href="tel:+497231123">a</a></p>'],
            'link in new tab' => ['<p><a href="https://example.com" target="_blank" rel="noopener noreferrer">a</a></p>'],
            'known t3 link' => ['<p><a href="t3://file?uid=1234">a</a></p>'],
            'known t3 link with entity' => ['<p><a href="t3://page?uid=12&amp;type=0">a</a></p>'],
            'umlauts, quotes and entities' => ['<p>äöü ß &amp; "x" &lt;y&gt; 10&nbsp;Uhr</p>'],
        ];
    }

    #[Test]
    #[DataProvider('allowedHtmlDataProvider')]
    public function sanitizeKeepsAllowedHtml(string $html): void
    {
        self::assertSame(
            $html,
            $this->subject->sanitize($html, self::ALLOWED_T3_HREFS),
        );
    }

    public static function forbiddenHtmlDataProvider(): array
    {
        return [
            'b is mapped to strong' => [
                '<p><b class="x">a</b></p>',
                '<p><strong>a</strong></p>',
            ],
            'i is mapped to em' => [
                '<p><i>a</i></p>',
                '<p><em>a</em></p>',
            ],
            'h1 and h4 become paragraphs' => [
                '<h1>a</h1><h4>b</h4><h5>c</h5><h6>d</h6>',
                '<p>a</p><p>b</p><p>c</p><p>d</p>',
            ],
            'unknown tags are removed, text is kept' => [
                '<p><u>a</u> <span style="color:red">b</span> <font>c</font></p><div>d</div><blockquote>e</blockquote>',
                '<p>a b c</p>de',
            ],
            'tables are removed, text is kept' => [
                '<table><tr><td>a</td><td>b</td></tr></table>',
                'ab',
            ],
            'script and style are removed with content' => [
                '<p>a<script>alert(1)</script><style>p{color:red}</style></p><script src="x.js"></script>',
                '<p>a</p>',
            ],
            'iframe, img, svg and form are removed with content' => [
                '<iframe src="https://example.com">x</iframe><img src="x.png" alt="y"><svg><text>z</text></svg><form><input value="v"></form>',
                '',
            ],
            'comments are removed' => [
                '<p>a<!-- comment --></p>',
                '<p>a</p>',
            ],
            'event handler attributes are removed' => [
                '<p onclick="alert(1)" onmouseover="alert(2)">a</p>',
                '<p>a</p>',
            ],
            'class, style, id and data attributes are removed' => [
                '<p class="x" style="color:red" id="y" data-list-item-id="z">a</p>',
                '<p>a</p>',
            ],
            'javascript link is removed, text is kept' => [
                '<p><a href="javascript:alert(1)">a</a></p>',
                '<p>a</p>',
            ],
            'obfuscated javascript link is removed' => [
                '<p><a href="java&#x09;script:alert(1)">a</a> <a href=" JaVaScRiPt:alert(1)">b</a></p>',
                '<p>a b</p>',
            ],
            'data link is removed' => [
                '<p><a href="data:text/html;base64,PHNjcmlwdD4=">a</a></p>',
                '<p>a</p>',
            ],
            'relative link is removed' => [
                '<p><a href="/page">a</a> <a href="#anchor">b</a></p>',
                '<p>a b</p>',
            ],
            'link without href is removed' => [
                '<p><a name="x">a</a></p>',
                '<p>a</p>',
            ],
            'other link attributes are removed' => [
                '<p><a href="https://example.com" class="x" title="y" onclick="z()" style="s">a</a></p>',
                '<p><a href="https://example.com">a</a></p>',
            ],
            'target other than _blank is removed with rel' => [
                '<p><a href="https://example.com" target="_self" rel="nofollow">a</a></p>',
                '<p><a href="https://example.com">a</a></p>',
            ],
            'rel of link in new tab is normalized' => [
                '<p><a href="https://example.com" target="_blank" rel="opener">a</a></p>',
                '<p><a href="https://example.com" target="_blank" rel="noopener noreferrer">a</a></p>',
            ],
            'surrounding whitespace of href is removed' => [
                '<p><a href=" https://example.com ">a</a></p>',
                '<p><a href="https://example.com">a</a></p>',
            ],
            't3 link with changed id is removed' => [
                '<p><a href="t3://file?uid=1235">a</a></p>',
                '<p>a</p>',
            ],
            'new t3 link is removed' => [
                '<p><a href="t3://page?uid=1">a</a></p>',
                '<p>a</p>',
            ],
            'modified known t3 link is removed' => [
                '<p><a href="t3://file?uid=1234&amp;x=1">a</a> <a href="T3://file?uid=1234">b</a></p>',
                '<p>a b</p>',
            ],
        ];
    }

    #[Test]
    #[DataProvider('forbiddenHtmlDataProvider')]
    public function sanitizeRemovesForbiddenHtml(string $html, string $expected): void
    {
        self::assertSame(
            $expected,
            $this->subject->sanitize($html, self::ALLOWED_T3_HREFS),
        );
    }

    #[Test]
    public function sanitizeWithoutAllowedT3HrefsRemovesAllT3Links(): void
    {
        self::assertSame(
            '<p>a</p>',
            $this->subject->sanitize('<p><a href="t3://file?uid=1234">a</a></p>'),
        );
    }

    #[Test]
    public function getT3HrefsReturnsDecodedT3HrefsOnly(): void
    {
        $html = '<p><a href="t3://page?uid=12&amp;type=0">a</a> <a class="x" href="https://example.com">b</a>'
            . ' <a href="t3://file?uid=1234" target="_blank">c</a> <a href="t3://file?uid=1234">d</a></p>';

        self::assertSame(
            ['t3://page?uid=12&type=0', 't3://file?uid=1234'],
            $this->subject->getT3Hrefs($html),
        );
    }
}
