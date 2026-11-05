<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Sanitizer;

use JWeiland\Events2\Event\ModifyRichTextSanitizerConfigurationEvent;
use JWeiland\Events2\Sanitizer\RichTextSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
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

        $this->subject = new RichTextSanitizer($this->createEventDispatcher(null));
    }

    /**
     * @param ?\Closure(ModifyRichTextSanitizerConfigurationEvent): void $listener
     */
    private function createEventDispatcher(?\Closure $listener): EventDispatcherInterface
    {
        return new class ($listener) implements EventDispatcherInterface {
            public function __construct(private readonly ?\Closure $listener) {}

            public function dispatch(object $event): object
            {
                if ($this->listener !== null && $event instanceof ModifyRichTextSanitizerConfigurationEvent) {
                    ($this->listener)($event);
                }

                return $event;
            }
        };
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
            'unknown inline tags are removed, text is kept' => [
                '<p><u>a</u> <span style="color:red">b</span> <font>c</font></p>',
                '<p>a b c</p>',
            ],
            'unknown block tags with inline content become paragraphs' => [
                '<div>d</div><blockquote>e</blockquote><pre>f</pre>',
                '<p>d</p><p>e</p><p>f</p>',
            ],
            'unknown block tags with blocks are unwrapped' => [
                '<div class="x"><p>a</p><div><h2>b</h2><p>c</p></div></div>',
                '<p>a</p><h2>b</h2><p>c</p>',
            ],
            'table cells become paragraphs' => [
                '<table><tr><td>a</td><td>b</td></tr></table>',
                '<p>a</p><p>b</p>',
            ],
            'b and i inside paragraph are mapped' => [
                '<p><b>a</b> <i>b</i></p>',
                '<p><strong>a</strong> <em>b</em></p>',
            ],
            'loose b is mapped and wrapped into paragraph' => [
                '<b>a</b>',
                '<p><strong>a</strong></p>',
            ],
            'loose b and i with attributes are mapped and wrapped into paragraph' => [
                '<b class="x" style="color:red">a</b> <i onclick="y()">b</i>',
                '<p><strong>a</strong> <em>b</em></p>',
            ],
            'loose inline content between blocks is wrapped into paragraphs' => [
                '<h2>a</h2>b <a href="https://example.com">c</a><br>d<ul><li>e</li></ul>f',
                '<h2>a</h2><p>b <a href="https://example.com">c</a><br>d</p><ul><li>e</li></ul><p>f</p>',
            ],
            'whitespace and line breaks between blocks are removed' => [
                "<p>a</p>\n <br> \n<p>b</p>",
                '<p>a</p><p>b</p>',
            ],
            'raw POST bypassing the editor' => [
                '<h1>H1</h1><h4>H4</h4><b>fett</b> <i>kursiv</i> <u>unter</u> <span style="font-size:40px">gross</span>'
                . '<table><tr><td>Zelle</td></tr></table><!-- kommentar -->',
                '<p>H1</p><p>H4</p><p><strong>fett</strong> <em>kursiv</em> unter gross</p><p>Zelle</p>',
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

    #[Test]
    public function sanitizeDispatchesEventWithTableAndColumn(): void
    {
        $dispatchedEvent = null;
        $subject = new RichTextSanitizer($this->createEventDispatcher(
            static function (ModifyRichTextSanitizerConfigurationEvent $event) use (&$dispatchedEvent): void {
                $dispatchedEvent = $event;
            },
        ));
        $subject->sanitize('<p>a</p>', [], 'tx_events2_domain_model_event', 'detail_information');

        self::assertInstanceOf(ModifyRichTextSanitizerConfigurationEvent::class, $dispatchedEvent);
        self::assertSame('tx_events2_domain_model_event', $dispatchedEvent->getTable());
        self::assertSame('detail_information', $dispatchedEvent->getColumn());
        self::assertContains('h2', $dispatchedEvent->getAllowedTags());
    }

    public static function modifiedConfigurationDataProvider(): array
    {
        return [
            'allowed h4 is kept instead of mapped' => [
                static fn(ModifyRichTextSanitizerConfigurationEvent $event) => $event->setAllowedTags([...$event->getAllowedTags(), 'h4']),
                '<h4>a</h4><p>b</p>',
                '<h4>a</h4><p>b</p>',
            ],
            'allowed block element is a top level block' => [
                static fn(ModifyRichTextSanitizerConfigurationEvent $event) => $event->setAllowedTags([...$event->getAllowedTags(), 'blockquote']),
                'a<blockquote>b</blockquote>',
                '<p>a</p><blockquote>b</blockquote>',
            ],
            'allowed elements get no attributes' => [
                static fn(ModifyRichTextSanitizerConfigurationEvent $event) => $event->setAllowedTags([...$event->getAllowedTags(), 'u']),
                '<p><u class="x" onclick="y()">a</u></p>',
                '<p><u>a</u></p>',
            ],
            'disallowed link keeps its text' => [
                static fn(ModifyRichTextSanitizerConfigurationEvent $event) => $event->setAllowedTags(array_diff($event->getAllowedTags(), ['a'])),
                '<p><a href="https://example.com">a</a></p>',
                '<p>a</p>',
            ],
            'disallowed heading becomes paragraph' => [
                static fn(ModifyRichTextSanitizerConfigurationEvent $event) => $event->setAllowedTags(array_diff($event->getAllowedTags(), ['h3'])),
                '<h3>a</h3>',
                '<p>a</p>',
            ],
            'paragraph can not be disallowed' => [
                static fn(ModifyRichTextSanitizerConfigurationEvent $event) => $event->setAllowedTags(array_diff($event->getAllowedTags(), ['p'])),
                '<p>a</p>b',
                '<p>a</p><p>b</p>',
            ],
            'mapping to disallowed element is ignored' => [
                static fn(ModifyRichTextSanitizerConfigurationEvent $event) => $event->setMappedTags(['u' => 'mark']),
                '<p><u>a</u></p>',
                '<p>a</p>',
            ],
            'additional mapping' => [
                static fn(ModifyRichTextSanitizerConfigurationEvent $event) => $event->setMappedTags([...$event->getMappedTags(), 'U' => 'EM']),
                '<p><u>a</u></p>',
                '<p><em>a</em></p>',
            ],
            'additional removed element is removed with content' => [
                static fn(ModifyRichTextSanitizerConfigurationEvent $event) => $event->setRemovedTags([...$event->getRemovedTags(), 'del']),
                '<p>a<del>b</del></p>',
                '<p>a</p>',
            ],
            'removed tags take precedence over allowed tags' => [
                static fn(ModifyRichTextSanitizerConfigurationEvent $event) => $event->setRemovedTags([...$event->getRemovedTags(), 'em']),
                '<p>a<em>b</em></p>',
                '<p>a</p>',
            ],
            'script can not be allowed' => [
                static function (ModifyRichTextSanitizerConfigurationEvent $event): void {
                    $event->setRemovedTags([]);
                    $event->setAllowedTags([...$event->getAllowedTags(), 'script', 'iframe']);
                },
                '<p>a</p><script>alert(1)</script><iframe src="https://example.com">b</iframe>',
                '<p>a</p>',
            ],
            'invalid tag names are ignored' => [
                static fn(ModifyRichTextSanitizerConfigurationEvent $event) => $event->setAllowedTags([...$event->getAllowedTags(), 'events2-unwrap', '', 'x y']),
                '<p>a</p>',
                '<p>a</p>',
            ],
        ];
    }

    /**
     * @param \Closure(ModifyRichTextSanitizerConfigurationEvent): void $listener
     */
    #[Test]
    #[DataProvider('modifiedConfigurationDataProvider')]
    public function sanitizeRespectsModifiedConfiguration(\Closure $listener, string $html, string $expected): void
    {
        $subject = new RichTextSanitizer($this->createEventDispatcher($listener));

        self::assertSame($expected, $subject->sanitize($html, self::ALLOWED_T3_HREFS));
    }
}
