<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Domain\Finisher;

use JWeiland\Events2\Domain\Finisher\SaveEventFinisher;
use JWeiland\Events2\Helper\PathSegmentHelper;
use JWeiland\Events2\Service\DayRelationService;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case.
 */
class SaveEventFinisherTest extends UnitTestCase
{
    protected SaveEventFinisher $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new SaveEventFinisher(
            self::createStub(ConnectionPool::class),
            self::createStub(PathSegmentHelper::class),
            self::createStub(DayRelationService::class),
        );
    }

    protected function tearDown(): void
    {
        unset($this->subject);

        parent::tearDown();
    }

    #[Test]
    public function convertPlainTextToHtmlWithEmptyStringReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->convertPlainTextToHtml(''),
        );
    }

    #[Test]
    public function convertPlainTextToHtmlWithSingleLineWrapsItInParagraph(): void
    {
        self::assertSame(
            '<p>Hello world</p>',
            $this->convertPlainTextToHtml('Hello world'),
        );
    }

    #[Test]
    public function convertPlainTextToHtmlWithSingleLineBreakConvertsItToBrTag(): void
    {
        self::assertSame(
            '<p>Hello<br />world</p>',
            $this->convertPlainTextToHtml("Hello\nworld"),
        );
    }

    #[Test]
    public function convertPlainTextToHtmlWithBlankLineStartsNewParagraph(): void
    {
        self::assertSame(
            "<p>First paragraph</p>\r\n<p>Second paragraph</p>",
            $this->convertPlainTextToHtml("First paragraph\n\nSecond paragraph"),
        );
    }

    #[Test]
    public function convertPlainTextToHtmlWithWindowsLineEndingsIsTreatedLikeUnixLineEndings(): void
    {
        self::assertSame(
            "<p>First paragraph</p>\r\n<p>Second paragraph</p>",
            $this->convertPlainTextToHtml("First paragraph\r\n\r\nSecond paragraph"),
        );
    }

    #[Test]
    public function convertPlainTextToHtmlStripsTagsTypedOrPastedByVisitor(): void
    {
        self::assertSame(
            '<p>alert("xss")bold text</p>',
            $this->convertPlainTextToHtml('<script>alert("xss")</script><b>bold text</b>'),
        );
    }

    #[Test]
    public function convertPlainTextToHtmlEscapesSpecialCharacters(): void
    {
        self::assertSame(
            '<p>Tom &amp; Jerry "quoted"</p>',
            $this->convertPlainTextToHtml('Tom & Jerry "quoted"'),
        );
    }

    #[Test]
    public function convertPlainTextToHtmlStoresDescriptionLikeBackendRte(): void
    {
        $plainText = "Erste Zeile der Beschreibung.\r\n"
            . "Zweite Zeile direkt darunter.\r\n"
            . "\r\n"
            . "Neuer Absatz nach einer Leerzeile.\r\n"
            . 'Letzte Zeile mit Umlauten: äöü ß & "Anführungszeichen".';

        self::assertSame(
            '<p>Erste Zeile der Beschreibung.<br />Zweite Zeile direkt darunter.</p>'
            . "\r\n"
            . '<p>Neuer Absatz nach einer Leerzeile.<br />Letzte Zeile mit Umlauten: äöü ß &amp; "Anführungszeichen".</p>',
            $this->convertPlainTextToHtml($plainText),
        );
    }

    #[Test]
    public function convertPlainTextToHtmlStoresNonBreakingSpaceAsEntity(): void
    {
        self::assertSame(
            '<p>Beginn 10&nbsp;Uhr</p>',
            $this->convertPlainTextToHtml("Beginn 10\u{00A0}Uhr"),
        );
    }

    private function convertPlainTextToHtml(string $plainText): string
    {
        $method = new \ReflectionMethod($this->subject, 'convertPlainTextToHtml');

        return $method->invoke($this->subject, $plainText);
    }
}
