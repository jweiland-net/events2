<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Domain\Finisher;

use JWeiland\Events2\Domain\Finisher\SaveEventFinisher;
use JWeiland\Events2\Helper\PathSegmentHelper;
use JWeiland\Events2\Service\DayRelationService;
use JWeiland\Events2\Service\RichTextService;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use JWeiland\Events2\Tests\Functional\Traits\InsertEventTrait;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for the description mapping of SaveEventFinisher
 * with the rich text editor disabled (convertPlainTextToHtml) and enabled
 * (sanitizeRichText).
 */
class SaveEventFinisherTest extends FunctionalTestCase
{
    use InsertEventTrait;

    private const EDITOR_HTML = '<h2>Programm</h2><p>Beginn 10&nbsp;Uhr<br>Einlass <b>9 Uhr</b></p>'
        . '<p onclick="alert(1)"><a href="javascript:alert(1)">Tickets</a> <a href="t3://page?uid=11">Seite</a></p>'
        . '<script>alert(1)</script>';

    protected array $coreExtensionsToLoad = [
        'form',
        'reactions',
        'rte_ckeditor',
    ];

    protected array $testExtensionsToLoad = [
        'jweiland/events2',
    ];

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'phpTimeZone' => Events2Constants::PHP_TIMEZONE,
        ],
    ];

    /**
     * UID of the edited event, which is part of the URI of the form
     */
    private int $eventUidToUpdate = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Events2PageTree.csv');
    }

    #[Test]
    public function prepareDataWithoutRichTextConvertsPlainText(): void
    {
        self::assertSame(
            "<p>Erste Zeile<br />Zweite Zeile &amp; mehr</p>\r\n<p>Absatz</p>",
            $this->prepareDescription(['convertPlainTextToHtml' => true], "Erste Zeile\r\nZweite Zeile & mehr\r\n\r\nAbsatz"),
        );
    }

    #[Test]
    public function prepareDataWithRichTextSanitizesNewEvent(): void
    {
        self::assertSame(
            "<h2>Programm</h2>\r\n<p>Beginn 10&nbsp;Uhr<br />Einlass <strong>9 Uhr</strong></p>\r\n<p>Tickets Seite</p>",
            $this->prepareDescription(['sanitizeRichText' => true], self::EDITOR_HTML),
        );
    }

    #[Test]
    public function prepareDataWithRichTextKeepsT3LinkOfEditedEvent(): void
    {
        $this->eventUidToUpdate = $this->insertEvent(
            title: 'Event with page link',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            additionalFields: ['detail_information' => '<p><a href="t3://page?uid=11">Seite</a></p>'],
        );

        self::assertSame(
            "<h2>Programm</h2>\r\n<p>Beginn 10&nbsp;Uhr<br />Einlass <strong>9 Uhr</strong></p>\r\n"
            . '<p>Tickets <a href="t3://page?uid=11">Seite</a></p>',
            $this->prepareDescription(['sanitizeRichText' => true], self::EDITOR_HTML),
        );
    }

    #[Test]
    public function prepareDataWithTwoRichTextElementsComparesT3LinksPerColumn(): void
    {
        $this->eventUidToUpdate = $this->insertEvent(
            title: 'Event with links in two columns',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            additionalFields: [
                'teaser' => '<p><a href="t3://page?uid=7">Teaser</a></p>',
                'detail_information' => '<p><a href="t3://page?uid=11">Seite</a></p>',
            ],
        );
        $value = '<p><b>Fett</b> <a href="t3://page?uid=7">Teaser</a> <a href="t3://page?uid=11">Seite</a></p>';

        $databaseData = $this->prepareData(
            [
                'program' => ['mapOnDatabaseColumn' => 'teaser', 'sanitizeRichText' => true],
                'detail-description' => ['mapOnDatabaseColumn' => 'detail_information', 'sanitizeRichText' => true],
            ],
            ['program' => $value, 'detail-description' => $value],
        );

        self::assertStringContainsString(
            '<strong>Fett</strong> <a href="t3://page?uid=7">Teaser</a> Seite',
            $databaseData['teaser'],
        );
        self::assertSame(
            '<p><strong>Fett</strong> Teaser <a href="t3://page?uid=11">Seite</a></p>',
            $databaseData['detail_information'],
        );
    }

    /**
     * @param array<string, mixed> $mapping Mapping of the description in the finisher
     */
    private function prepareDescription(array $mapping, string $value): string
    {
        return $this->prepareData(
            ['detail-description' => ['mapOnDatabaseColumn' => 'detail_information'] + $mapping],
            ['detail-description' => $value],
        )['detail_information'];
    }

    /**
     * @param array<string, array<string, mixed>> $elementsConfiguration
     * @param array<string, string> $formValues
     * @return array<string, mixed>
     */
    private function prepareData(array $elementsConfiguration, array $formValues): array
    {
        $subject = new class (
            $this->get(ConnectionPool::class),
            $this->get(PathSegmentHelper::class),
            $this->get(DayRelationService::class),
            richTextService: $this->get(RichTextService::class),
        ) extends SaveEventFinisher {
            public array $formValues = [];

            public int $eventUidToUpdate = 0;

            protected function getEventUidToUpdate(): int
            {
                return $this->eventUidToUpdate;
            }

            public function setOptions(array $options): void
            {
                $this->options = $options;
            }

            protected function getFormValues(): array
            {
                return $this->formValues;
            }

            protected function getElementByIdentifier(string $elementIdentifier): ?FormElementInterface
            {
                return new GenericFormElement($elementIdentifier, 'Textarea');
            }

            protected function parseOption(string $optionName): string|array|int|null
            {
                return $this->options[$optionName] ?? null;
            }
        };
        $subject->formValues = $formValues;
        $subject->eventUidToUpdate = $this->eventUidToUpdate;
        $subject->setOptions(['table' => 'tx_events2_domain_model_event', 'mode' => 'insert']);

        return (new \ReflectionMethod($subject, 'prepareData'))->invoke(
            $subject,
            $elementsConfiguration,
            ['pid' => Events2Constants::PAGE_STORAGE],
        );
    }
}
