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
            "<p>Hello<br />\nworld</p>",
            $this->convertPlainTextToHtml("Hello\nworld"),
        );
    }

    #[Test]
    public function convertPlainTextToHtmlWithBlankLineStartsNewParagraph(): void
    {
        self::assertSame(
            '<p>First paragraph</p><p>Second paragraph</p>',
            $this->convertPlainTextToHtml("First paragraph\n\nSecond paragraph"),
        );
    }

    #[Test]
    public function convertPlainTextToHtmlWithWindowsLineEndingsIsTreatedLikeUnixLineEndings(): void
    {
        self::assertSame(
            '<p>First paragraph</p><p>Second paragraph</p>',
            $this->convertPlainTextToHtml("First paragraph\r\n\r\nSecond paragraph"),
        );
    }

    #[Test]
    public function convertPlainTextToHtmlStripsTagsTypedOrPastedByVisitor(): void
    {
        self::assertSame(
            '<p>alert(&quot;xss&quot;)bold text</p>',
            $this->convertPlainTextToHtml('<script>alert("xss")</script><b>bold text</b>'),
        );
    }

    #[Test]
    public function convertPlainTextToHtmlEscapesSpecialCharacters(): void
    {
        self::assertSame(
            '<p>Tom &amp; Jerry &quot;quoted&quot;</p>',
            $this->convertPlainTextToHtml('Tom & Jerry "quoted"'),
        );
    }

    private function convertPlainTextToHtml(string $plainText): string
    {
        $method = new \ReflectionMethod($this->subject, 'convertPlainTextToHtml');

        return $method->invoke($this->subject, $plainText);
    }
}
