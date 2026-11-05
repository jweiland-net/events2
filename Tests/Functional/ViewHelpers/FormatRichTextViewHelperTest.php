<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\ViewHelpers;

use JWeiland\Events2\Event\ModifyRichTextSanitizerConfigurationEvent;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3Fluid\Fluid\View\TemplateView;

/**
 * Functional test
 */
class FormatRichTextViewHelperTest extends FunctionalTestCase
{
    private const RICH_TEXT = '<h2 data-list-item-id="e1">Program</h2>'
        . '<p>Start at <strong>10 o\'clock</strong></p>'
        . '<script>alert("xss")</script>'
        . '<ul><li data-list-item-id="e2">Music</li><li data-list-item-id="e3">Food</li></ul>'
        . '<p><a href="https://example.com" onclick="alert(1)">Tickets</a></p>';

    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'form',
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'sjbr/static-info-tables',
        'jweiland/events2',
    ];

    #[Test]
    public function renderReturnsSanitizedHtml(): void
    {
        $output = $this->renderViewHelper(self::RICH_TEXT, 'html');

        self::assertStringContainsString('<h2>Program</h2>', $output);
        self::assertStringContainsString('<strong>10 o\'clock</strong>', $output);
        self::assertStringContainsString('<ul><li>Music</li><li>Food</li></ul>', $output);
        self::assertStringContainsString('>Tickets</a>', $output);
        self::assertStringNotContainsString('data-list-item-id', $output);
        self::assertStringNotContainsString('<script', $output);
        self::assertStringNotContainsString('alert', $output);
        self::assertStringNotContainsString('onclick', $output);
    }

    #[Test]
    public function renderReturnsPlainTextWithLineBreaks(): void
    {
        $output = $this->renderViewHelper(self::RICH_TEXT, 'plaintext');

        self::assertSame(
            "Program\n\nStart at 10 o'clock\n\nMusic\nFood\n\nTickets",
            $output,
        );
    }

    #[Test]
    public function renderUsesSanitizerConfigurationOfTableAndColumn(): void
    {
        $tablesAndColumns = [];
        $this->get(ListenerProvider::class)->addListener(
            ModifyRichTextSanitizerConfigurationEvent::class,
            'events2-test-listener',
        );
        $this->getContainer()->set(
            'events2-test-listener',
            static function (ModifyRichTextSanitizerConfigurationEvent $event) use (&$tablesAndColumns): void {
                $tablesAndColumns[] = $event->getTable() . '.' . $event->getColumn();
            },
        );

        $this->renderViewHelper('<p>Text</p>', 'html');

        self::assertSame(
            ['tx_events2_domain_model_event.detail_information'],
            $tablesAndColumns,
        );
    }

    #[Test]
    public function renderWithEmptyValueReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->renderViewHelper('   ', 'html'),
        );
    }

    #[Test]
    public function renderWithInvalidFormatThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1762963201);

        $this->renderViewHelper('<p>Text</p>', 'pdf');
    }

    public static function mailTemplateDataProvider(): array
    {
        return [
            'HTML part' => ['Default.html'],
            'Text part' => ['Default.txt'],
        ];
    }

    #[Test]
    #[DataProvider('mailTemplateDataProvider')]
    public function mailTemplateUsesViewHelperForRichTextElements(string $templateFile): void
    {
        $templateSource = (string)file_get_contents(
            __DIR__ . '/../../../Resources/Private/FormFramework/Templates/Finishers/Email/' . $templateFile,
        );
        $context = $this->get(RenderingContextFactory::class)->create();

        // Throws an exception on invalid syntax or unknown ViewHelpers
        $context->getTemplateParser()->parse($templateSource);

        self::assertStringContainsString('<e2:formatRichText element="{formValue.element}"', $templateSource);
    }

    private function renderViewHelper(string $value, string $format): string
    {
        $element = new GenericFormElement('detail-description', 'Textarea');
        $element->setProperty('dbMapping', [
            'column' => 'detail_information',
            'dataType' => 'richText',
        ]);

        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getVariableProvider()->add('element', $element);
        $context->getVariableProvider()->add('value', $value);
        $context->getVariableProvider()->add('format', $format);
        $context->getTemplatePaths()->setTemplateSource(
            '{namespace e2=JWeiland\Events2\ViewHelpers}'
            . '<e2:formatRichText element="{element}" value="{value}" format="{format}"/>',
        );

        return trim((new TemplateView($context))->render());
    }
}
