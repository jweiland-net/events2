<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\ViewHelpers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

/**
 * Functional test for ConvertHtmlToPlainTextViewHelper using TYPO3 13+ RenderingContext
 */
class ConvertHtmlToPlainTextViewHelperTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'sjbr/static-info-tables',
        'jweiland/events2',
    ];

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function htmlDataProvider(): array
    {
        return [
            'Empty string stays empty' => [
                '',
                '',
            ],
            'Plain text without tags stays untouched' => [
                'Hello world',
                'Hello world',
            ],
            'Closing paragraph tag becomes a blank line' => [
                '<p>First paragraph</p><p>Second paragraph</p>',
                "First paragraph\n\nSecond paragraph",
            ],
            'Br tag becomes a line break' => [
                'Hello<br />world',
                "Hello\nworld",
            ],
            'Self-closing br tag becomes a line break' => [
                'Hello<br/>world',
                "Hello\nworld",
            ],
            'Formatting tags are stripped but their text survives' => [
                '<p><b>Bold</b> and <a href="https://example.com">a link</a></p>',
                'Bold and a link',
            ],
            'Script tag markup is stripped, its text content remains but is escaped' => [
                '<script>alert("xss")</script>Hello',
                'alert(&quot;xss&quot;)Hello',
            ],
            'Special characters are escaped' => [
                '<p>Tom & Jerry "quoted"</p>',
                'Tom &amp; Jerry &quot;quoted&quot;',
            ],
            'Entities are decoded before they get escaped once' => [
                '<p>Tom &amp; Jerry &quot;quoted&quot; &lt;b&gt; &auml;</p>',
                'Tom &amp; Jerry &quot;quoted&quot; &lt;b&gt; ä',
            ],
            'Backend RTE format' => [
                "<p>First line<br />Second line</p>\r\n<p>New paragraph</p>",
                "First line\nSecond line\n\nNew paragraph",
            ],
            'Inline non-breaking space is kept' => [
                'Hello&nbsp;world',
                "Hello\u{00A0}world",
            ],
            'Paragraph with non-breaking space only is removed' => [
                "<p>Beginn 10&nbsp;Uhr</p>\r\n<p>&nbsp;</p>\r\n<p>Zweiter Absatz</p>",
                "Beginn 10\u{00A0}Uhr\n\nZweiter Absatz",
            ],
            'Legacy frontend format with newline after br' => [
                "<p>First line<br />\nSecond line</p><p>New paragraph</p>",
                "First line\nSecond line\n\nNew paragraph",
            ],
        ];
    }

    #[Test]
    #[DataProvider('htmlDataProvider')]
    public function renderConvertsHtmlToEscapedPlainText(string $inputValue, string $expectedPlainText): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getVariableProvider()->add('inputValue', $inputValue);

        $templateSource = '
            {namespace e2=JWeiland\Events2\ViewHelpers}
            <f:format.raw>{inputValue -> e2:convertHtmlToPlainText()}</f:format.raw>';

        $context->getTemplatePaths()->setTemplateSource($templateSource);

        $view = new TemplateView($context);
        $output = trim($view->render());

        self::assertSame(
            $expectedPlainText,
            $output,
        );
    }
}
