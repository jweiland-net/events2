<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\ViewHelpers;

use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Service\ManagementAccessService;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use JWeiland\Events2\Tests\Functional\Traits\InsertEventTrait;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

/**
 * Functional test for ModerationTokenViewHelper
 */
class ModerationTokenViewHelperTest extends FunctionalTestCase
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

    #[Test]
    public function renderWithEventUidReturnsModerationToken(): void
    {
        $eventUid = $this->insertHiddenEvent();

        self::assertSame(
            $this->get(ManagementAccessService::class)->generateModerationToken($eventUid),
            $this->renderModerationToken($eventUid),
        );
    }

    #[Test]
    public function renderWithEventUidAsStringReturnsModerationToken(): void
    {
        $eventUid = $this->insertHiddenEvent();

        self::assertSame(
            $this->get(ManagementAccessService::class)->generateModerationToken($eventUid),
            $this->renderModerationToken((string)$eventUid),
        );
    }

    #[Test]
    public function renderWithEventObjectReturnsModerationToken(): void
    {
        $eventUid = $this->insertHiddenEvent();
        $event = new Event();
        $event->_setProperty('uid', $eventUid);

        self::assertSame(
            $this->get(ManagementAccessService::class)->generateModerationToken($eventUid),
            $this->renderModerationToken($event),
        );
    }

    #[Test]
    public function renderWithEmptyEventReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->renderModerationToken(''),
        );
    }

    #[Test]
    public function renderWithNotExistingEventReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            $this->renderModerationToken(4711),
        );
    }

    private function insertHiddenEvent(): int
    {
        return $this->insertEvent(
            title: 'Hidden event',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            additionalFields: ['hidden' => 1],
        );
    }

    private function renderModerationToken(mixed $event): string
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getVariableProvider()->add('event', $event);
        $context->getTemplatePaths()->setTemplateSource(
            '{namespace e2=JWeiland\Events2\ViewHelpers}{e2:moderationToken(event: event)}',
        );

        return trim((new TemplateView($context))->render());
    }
}
