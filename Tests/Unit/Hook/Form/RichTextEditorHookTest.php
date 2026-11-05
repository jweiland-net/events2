<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Hook\Form;

use JWeiland\Events2\Event\ModifyRichTextSanitizerConfigurationEvent;
use JWeiland\Events2\Form\RichTextEditorAssets;
use JWeiland\Events2\Hook\Form\RichTextEditorHook;
use JWeiland\Events2\Sanitizer\RichTextSanitizer;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case.
 */
class RichTextEditorHookTest extends UnitTestCase
{
    private RichTextEditorAssets $richTextEditorAssets;

    private FormRuntime $formRuntime;

    private RichTextEditorHook $subject;

    /**
     * @var ModifyRichTextSanitizerConfigurationEvent[]
     */
    private array $dispatchedEvents = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->richTextEditorAssets = $this->createMock(RichTextEditorAssets::class);
        $this->richTextEditorAssets
            ->method('getEditorOptions')
            ->willReturnOnConsecutiveCalls(['language' => 'de'], ['language' => 'en']);

        $this->formRuntime = $this->createMock(FormRuntime::class);
        $this->formRuntime->method('getRequest')->willReturn($this->createMock(Request::class));

        $this->subject = new RichTextEditorHook($this->richTextEditorAssets, new RichTextSanitizer($this->createEventDispatcher()));
    }

    private function createEventDispatcher(): EventDispatcherInterface
    {
        $dispatchedEvents = &$this->dispatchedEvents;

        return new class ($dispatchedEvents) implements EventDispatcherInterface {
            /**
             * @param ModifyRichTextSanitizerConfigurationEvent[] $dispatchedEvents
             */
            public function __construct(private array &$dispatchedEvents) {}

            public function dispatch(object $event): object
            {
                if ($event instanceof ModifyRichTextSanitizerConfigurationEvent) {
                    $event->setAllowedTags([...$event->getAllowedTags(), 'h4']);
                    $this->dispatchedEvents[] = $event;
                }

                return $event;
            }
        };
    }

    #[Test]
    public function beforeRenderingPassesConfigurationOfColumnToEditorOptions(): void
    {
        $element = $this->buildElement('detail-description', 'richText');
        $this->richTextEditorAssets
            ->expects(self::once())
            ->method('getEditorOptions')
            ->with(
                self::anything(),
                self::callback(static fn($configuration): bool => in_array('h4', $configuration->allowedTags, true)),
            );

        $this->subject->beforeRendering($this->formRuntime, $element);

        self::assertCount(1, $this->dispatchedEvents);
        self::assertSame('tx_events2_domain_model_event', $this->dispatchedEvents[0]->getTable());
        self::assertSame('detail_information', $this->dispatchedEvents[0]->getColumn());
    }

    #[Test]
    public function beforeRenderingAddsEditorToRichTextElement(): void
    {
        $element = $this->buildElement('detail-description', 'richText');
        $this->richTextEditorAssets->expects(self::once())->method('load');

        $this->subject->beforeRendering($this->formRuntime, $element);

        self::assertSame(
            ['class' => 'x', 'data-events2-rich-text' => '{"language":"de"}'],
            $element->getProperties()['fluidAdditionalAttributes'],
        );
    }

    #[Test]
    public function beforeRenderingAddsEditorToEachRichTextElement(): void
    {
        $firstElement = $this->buildElement('detail-description', 'richText');
        $secondElement = $this->buildElement('program', 'richText');
        $this->richTextEditorAssets->expects(self::exactly(2))->method('load');

        $this->subject->beforeRendering($this->formRuntime, $firstElement);
        $this->subject->beforeRendering($this->formRuntime, $secondElement);

        self::assertSame('{"language":"de"}', $firstElement->getProperties()['fluidAdditionalAttributes']['data-events2-rich-text']);
        self::assertSame('{"language":"en"}', $secondElement->getProperties()['fluidAdditionalAttributes']['data-events2-rich-text']);
    }

    #[Test]
    public function beforeRenderingIgnoresPlainTextElement(): void
    {
        $element = $this->buildElement('short-description', 'plainText');
        $this->richTextEditorAssets->expects(self::never())->method('load');

        $this->subject->beforeRendering($this->formRuntime, $element);

        self::assertSame(['class' => 'x'], $element->getProperties()['fluidAdditionalAttributes']);
    }

    #[Test]
    public function beforeRenderingIgnoresFormDefinition(): void
    {
        $this->richTextEditorAssets->expects(self::never())->method('load');

        $this->subject->beforeRendering($this->formRuntime, new FormDefinition('newEvent'));
    }

    private function buildElement(string $identifier, string $dataType): GenericFormElement
    {
        $element = new GenericFormElement($identifier, 'Textarea');
        $element->setProperty('dbMapping', ['column' => 'detail_information', 'dataType' => $dataType]);
        $element->setProperty('fluidAdditionalAttributes', ['class' => 'x']);

        return $element;
    }
}
