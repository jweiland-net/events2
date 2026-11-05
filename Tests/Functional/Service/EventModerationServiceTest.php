<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Service;

use JWeiland\Events2\Service\EventModerationService;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use JWeiland\Events2\Tests\Functional\Traits\InsertEventTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test
 */
class EventModerationServiceTest extends FunctionalTestCase
{
    use InsertEventTrait;

    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'reactions',
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

    private EventModerationService $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->get(EventModerationService::class);
    }

    #[Test]
    public function generateModerationTokenReturnsTokenForExistingEvent(): void
    {
        $eventUid = $this->insertEvent('Event', new \DateTimeImmutable('tomorrow midnight'));

        self::assertNotSame(
            '',
            $this->subject->generateModerationToken($eventUid),
        );
    }

    #[Test]
    public function generateModerationTokenReturnsTokenForHiddenEvent(): void
    {
        $eventUid = $this->insertHiddenEvent();

        self::assertNotSame(
            '',
            $this->subject->generateModerationToken($eventUid),
        );
    }

    #[Test]
    public function generateModerationTokenReturnsDifferentTokensForDifferentEvents(): void
    {
        $firstEventUid = $this->insertHiddenEvent();
        $secondEventUid = $this->insertHiddenEvent();

        self::assertNotSame(
            $this->subject->generateModerationToken($firstEventUid),
            $this->subject->generateModerationToken($secondEventUid),
        );
    }

    public static function invalidEventUidDataProvider(): array
    {
        return [
            'zero' => [0],
            'negative' => [-1],
            'not existing' => [4711],
        ];
    }

    #[Test]
    #[DataProvider('invalidEventUidDataProvider')]
    public function generateModerationTokenForInvalidEventUidReturnsEmptyString(int $eventUid): void
    {
        self::assertSame(
            '',
            $this->subject->generateModerationToken($eventUid),
        );
    }

    #[Test]
    public function generateModerationTokenForDeletedEventReturnsEmptyString(): void
    {
        $eventUid = $this->insertEvent(
            'Deleted event',
            new \DateTimeImmutable('tomorrow midnight'),
            additionalFields: ['deleted' => 1],
        );

        self::assertSame(
            '',
            $this->subject->generateModerationToken($eventUid),
        );
    }

    #[Test]
    public function moderationTokenStaysValidAfterEventWasModified(): void
    {
        $eventUid = $this->insertHiddenEvent();
        $moderationToken = $this->subject->generateModerationToken($eventUid);

        $this->getConnectionPool()
            ->getConnectionForTable('tx_events2_domain_model_event')
            ->update('tx_events2_domain_model_event', ['tstamp' => time() + 60], ['uid' => $eventUid]);

        self::assertSame(
            $moderationToken,
            $this->subject->getValidModerationToken($this->buildRequest($moderationToken), $eventUid),
        );
    }

    #[Test]
    public function getValidModerationTokenReturnsValidToken(): void
    {
        $eventUid = $this->insertHiddenEvent();
        $moderationToken = $this->subject->generateModerationToken($eventUid);

        self::assertSame(
            $moderationToken,
            $this->subject->getValidModerationToken($this->buildRequest($moderationToken), $eventUid),
        );
    }

    #[Test]
    public function getValidModerationTokenWithoutTokenArgumentReturnsEmptyString(): void
    {
        $eventUid = $this->insertHiddenEvent();

        self::assertSame(
            '',
            $this->subject->getValidModerationToken($this->buildRequest(null), $eventUid),
        );
    }

    public static function invalidModerationTokenDataProvider(): array
    {
        return [
            'empty token' => [''],
            'invalid token' => ['invalid'],
            'array token' => [['foo' => 'bar']],
        ];
    }

    #[Test]
    #[DataProvider('invalidModerationTokenDataProvider')]
    public function getValidModerationTokenWithInvalidTokenReturnsEmptyString(mixed $moderationToken): void
    {
        $eventUid = $this->insertHiddenEvent();

        self::assertSame(
            '',
            $this->subject->getValidModerationToken($this->buildRequest($moderationToken), $eventUid),
        );
    }

    #[Test]
    public function getValidModerationTokenWithTokenOfForeignEventReturnsEmptyString(): void
    {
        $foreignEventUid = $this->insertHiddenEvent();
        $eventUid = $this->insertHiddenEvent();
        $moderationToken = $this->subject->generateModerationToken($foreignEventUid);

        self::assertSame(
            '',
            $this->subject->getValidModerationToken($this->buildRequest($moderationToken), $eventUid),
        );
    }

    #[Test]
    public function getValidModerationTokenForDeletedEventReturnsEmptyString(): void
    {
        $eventUid = $this->insertHiddenEvent();
        $moderationToken = $this->subject->generateModerationToken($eventUid);

        $this->getConnectionPool()
            ->getConnectionForTable('tx_events2_domain_model_event')
            ->update('tx_events2_domain_model_event', ['deleted' => 1], ['uid' => $eventUid]);

        self::assertSame(
            '',
            $this->subject->getValidModerationToken($this->buildRequest($moderationToken), $eventUid),
        );
    }

    private function insertHiddenEvent(): int
    {
        return $this->insertEvent(
            'Hidden event',
            new \DateTimeImmutable('tomorrow midnight'),
            additionalFields: ['hidden' => 1],
        );
    }

    private function buildRequest(mixed $moderationToken): Request
    {
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $extbaseRequestParameters->setControllerExtensionName('Events2');
        $extbaseRequestParameters->setPluginName('Management');
        $extbaseRequestParameters->setControllerName('Management');
        $extbaseRequestParameters->setControllerActionName('activate');
        if ($moderationToken !== null) {
            $extbaseRequestParameters->setArgument('moderationToken', $moderationToken);
        }

        return new Request(
            (new ServerRequest('https://www.example.com/', 'POST'))
                ->withAttribute('extbase', $extbaseRequestParameters),
        );
    }
}
