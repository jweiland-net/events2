<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Service;

use JWeiland\Events2\Service\RichTextService;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use JWeiland\Events2\Tests\Functional\Traits\InsertEventTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test
 */
class RichTextServiceTest extends FunctionalTestCase
{
    use InsertEventTrait;

    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'reactions',
        'rte_ckeditor',
    ];

    protected array $testExtensionsToLoad = [
        'sjbr/static-info-tables',
        'jweiland/events2',
    ];

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'phpTimeZone' => Events2Constants::PHP_TIMEZONE,
        ],
    ];

    private RichTextService $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Events2PageTree.csv');
        $this->subject = $this->get(RichTextService::class);
    }

    public static function editorHtmlDataProvider(): array
    {
        return [
            'example of CKEditor' => [
                '<p>Erste Zeile der Beschreibung.<br>Zweite Zeile direkt darunter.</p>'
                . '<p>Neuer Absatz nach einer Leerzeile.<br>Letzte Zeile mit Umlauten: äöü ß &amp; "Anführungszeichen".</p>',
                '<p>Erste Zeile der Beschreibung.<br />Zweite Zeile direkt darunter.</p>' . "\r\n"
                . '<p>Neuer Absatz nach einer Leerzeile.<br />Letzte Zeile mit Umlauten: äöü ß &amp; "Anführungszeichen".</p>',
            ],
            'headings, lists and formatting' => [
                '<h2>Programm</h2><ul><li><strong>Fett</strong></li><li><em>Kursiv</em></li></ul>'
                . '<h3>Preise</h3><ol><li>10&nbsp;Euro</li></ol>',
                "<h2>Programm</h2>\r\n<ul><li><strong>Fett</strong></li><li><em>Kursiv</em></li></ul>\r\n"
                . "<h3>Preise</h3>\r\n<ol><li>10&nbsp;Euro</li></ol>",
            ],
            'link opening in new tab' => [
                '<p><a href="https://example.com" target="_blank" rel="noopener noreferrer">Link</a></p>',
                '<p><a href="https://example.com" target="_blank" rel="noopener noreferrer">Link</a></p>',
            ],
            'forbidden markup is removed' => [
                '<p onclick="alert(1)" style="color:red">Text<script>alert(1)</script></p><img src="x.png">',
                '<p>Text</p>',
            ],
            'plain text without JavaScript is converted like the plain textarea' => [
                "Erste Zeile\r\nZweite Zeile & mehr\r\n\r\nAbsatz",
                "<p>Erste Zeile<br />Zweite Zeile &amp; mehr</p>\r\n<p>Absatz</p>",
            ],
        ];
    }

    #[Test]
    #[DataProvider('editorHtmlDataProvider')]
    public function prepareForPersistenceReturnsHtmlOfBackend(string $html, string $expected): void
    {
        self::assertSame(
            $expected,
            $this->subject->prepareForPersistence($html, 0, Events2Constants::PAGE_STORAGE),
        );
    }

    #[Test]
    public function prepareForPersistenceKeepsUnchangedT3LinkOfStoredEvent(): void
    {
        $eventUid = $this->insertEvent(
            title: 'Event with file link',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            additionalFields: [
                // RteHtmlParser removes links to not existing records. Page 11 exists.
                'detail_information' => '<p><a href="t3://page?uid=11">Programm</a></p>',
            ],
        );

        self::assertSame(
            '<p><a href="t3://page?uid=11">Programm</a> und neu</p>',
            $this->subject->prepareForPersistence(
                '<p><a href="t3://page?uid=11">Programm</a> und <a href="t3://page?uid=2">neu</a></p>',
                $eventUid,
            ),
        );
    }

    #[Test]
    public function prepareForPersistenceRemovesT3LinkOfNewEvent(): void
    {
        self::assertSame(
            '<p>Programm</p>',
            $this->subject->prepareForPersistence('<p><a href="t3://page?uid=11">Programm</a></p>', 0),
        );
    }
}
