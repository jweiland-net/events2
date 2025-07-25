<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Helper;

use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Domain\Repository\LocationRepository;
use JWeiland\Events2\Helper\HiddenObjectHelper;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Persistence\Generic\Session;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test
 */
class HiddenObjectHelperTest extends FunctionalTestCase
{
    protected HiddenObjectHelper $subject;

    protected Session $session;

    protected EventRepository|MockObject $eventRepositoryMock;

    protected Request|MockObject $requestMock;

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

    protected function setUp(): void
    {
        parent::setUp();

        $this->session = $this->get(Session::class);
        $this->eventRepositoryMock = $this->createMock(EventRepository::class);
        $this->requestMock = $this->createMock(Request::class);

        $this->subject = GeneralUtility::makeInstance(
            HiddenObjectHelper::class,
            $this->session,
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
            $this->session,
        );

        parent::tearDown();
    }

    #[Test]
    public function registerWithInvalidRepositoryWillNotAddObjectToSession(): void
    {
        /** @var LocationRepository|MockObject $locationRepositoryMock */
        $locationRepositoryMock = $this->createMock(LocationRepository::class);

        $event = new Event();

        $this->requestMock
            ->expects(self::never())
            ->method('getArgument')
            ->with(self::identicalTo('event'));

        $this->subject->registerHiddenObjectInExtbaseSession(
            $locationRepositoryMock,
            $this->requestMock,
            'event',
        );

        self::assertFalse(
            $this->session->hasObject($event),
        );
    }

    #[Test]
    public function registerWithRepositoryWillAddObjectByArrayToSession(): void
    {
        $event = new Event();
        $event->_setProperty('uid', 12);
        $event->setTitle('Test Event');

        $this->requestMock
            ->expects(self::atLeastOnce())
            ->method('getArgument')
            ->with(self::identicalTo('event'))
            ->willReturn([
                '__identity' => '12',
            ]);

        $this->eventRepositoryMock
            ->expects(self::atLeastOnce())
            ->method('findHiddenObject')
            ->with(self::identicalTo(12))
            ->willReturn($event);

        $this->subject->registerHiddenObjectInExtbaseSession(
            $this->eventRepositoryMock,
            $this->requestMock,
            'event',
        );

        self::assertTrue(
            $this->session->hasObject($event),
        );
        self::assertSame(
            $event,
            $this->session->getObjectByIdentifier('12', Event::class),
        );
    }

    #[Test]
    public function registerWithRepositoryWillAddObjectByUidToSession(): void
    {
        $event = new Event();
        $event->_setProperty('uid', 543);
        $event->setTitle('Test Event');

        $this->requestMock
            ->expects(self::atLeastOnce())
            ->method('getArgument')
            ->with(self::identicalTo('event'))
            ->willReturn('543');

        $this->eventRepositoryMock
            ->expects(self::atLeastOnce())
            ->method('findHiddenObject')
            ->with(self::identicalTo(543))
            ->willReturn($event);

        $this->subject->registerHiddenObjectInExtbaseSession(
            $this->eventRepositoryMock,
            $this->requestMock,
            'event',
        );

        self::assertTrue(
            $this->session->hasObject($event),
        );
        self::assertSame(
            $event,
            $this->session->getObjectByIdentifier('543', Event::class),
        );
    }
}
