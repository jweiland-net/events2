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

    /**
     * @var EventRepository|MockObject
     */
    protected $eventRepositoryMock;

    /**
     * @var Request|MockObject
     */
    protected $requestMock;

    protected array $coreExtensionsToLoad = [
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'jweiland/events2',
    ];

    protected function setUp(): void
    {
        self::markTestIncomplete('HiddenObjectHelperTest not updated until right now');

        parent::setUp();

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->session = $objectManager->get(Session::class);
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

    /**
     * @test
     */
    public function registerWithInvalidRepositoryWillNotAddObjectToSession(): void
    {
        /** @var LocationRepository|MockObject $locationRepositoryMock */
        $locationRepositoryMock = $this->createMock(LocationRepository::class);
        $event = GeneralUtility::makeInstance(Event::class);
        $this->requestMock
            ->getArgument('event')
            ->shouldNotBeCalled();

        $this->subject->registerHiddenObjectInExtbaseSession(
            $locationRepositoryMock,
            $this->requestMock,
            'event',
        );

        self::assertFalse(
            $this->session->hasObject($event),
        );
    }

    /**
     * @test
     */
    public function registerWithRepositoryWillAddObjectByArrayToSession(): void
    {
        $event = GeneralUtility::makeInstance(Event::class);
        $event->_setProperty('uid', 12);
        $event->setTitle('Test Event');

        $this->requestMock
            ->getArgument('event')
            ->shouldBeCalled()
            ->willReturn([
                '__identity' => '12',
            ]);

        $this->eventRepositoryMock
            ->findHiddenObject(12)
            ->shouldBeCalled()
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
            $this->session->getObjectByIdentifier(12, Event::class),
        );
    }

    /**
     * @test
     */
    public function registerWithRepositoryWillAddObjectByUidToSession(): void
    {
        $event = GeneralUtility::makeInstance(Event::class);
        $event->_setProperty('uid', 543);
        $event->setTitle('Test Event');

        $this->requestMock
            ->getArgument('event')
            ->shouldBeCalled()
            ->willReturn('543');

        $this->eventRepositoryMock
            ->findHiddenObject(543)
            ->shouldBeCalled()
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
            $this->session->getObjectByIdentifier(543, Event::class),
        );
    }
}
