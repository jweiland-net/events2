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
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Session;

/**
 * Functional test
 */
class HiddenObjectHelperTest extends FunctionalTestCase
{
    /**
     * @var HiddenObjectHelper
     */
    protected $subject;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var EventRepository|ObjectProphecy
     */
    protected $eventRepositoryProphecy;

    /**
     * @var Request|ObjectProphecy
     */
    protected $requestProphecy;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/events2'
    ];

    public function setUp(): void
    {
        parent::setUp();

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->session = $objectManager->get(Session::class);
        $this->eventRepositoryProphecy = $this->prophesize(EventRepository::class);
        $this->requestProphecy = $this->prophesize(Request::class);

        $this->subject = GeneralUtility::makeInstance(
            HiddenObjectHelper::class,
            $this->session
        );
    }

    public function tearDown(): void
    {
        unset(
            $this->subject,
            $this->session
        );

        parent::tearDown();
    }

    /**
     * @test
     */
    public function registerWithInvalidRepositoryWillNotAddObjectToSession(): void
    {
        /** @var LocationRepository|ObjectProphecy $locationRepositoryProphecy */
        $locationRepositoryProphecy = $this->prophesize(LocationRepository::class);
        $event = new Event();
        $this->requestProphecy
            ->getArgument('event')
            ->shouldNotBeCalled();

        $this->subject->registerHiddenObjectInExtbaseSession(
            $locationRepositoryProphecy->reveal(),
            $this->requestProphecy->reveal(),
            'event'
        );

        self::assertFalse(
            $this->session->hasObject($event)
        );
    }

    /**
     * @test
     */
    public function registerWithRepositoryWillAddObjectByArrayToSession(): void
    {
        $event = new Event();
        $event->_setProperty('uid', 12);
        $event->setTitle('Test Event');

        $this->requestProphecy
            ->getArgument('event')
            ->shouldBeCalled()
            ->willReturn([
                '__identity' => '12'
            ]);

        $this->eventRepositoryProphecy
            ->findHiddenObject(12)
            ->shouldBeCalled()
            ->willReturn($event);

        $this->subject->registerHiddenObjectInExtbaseSession(
            $this->eventRepositoryProphecy->reveal(),
            $this->requestProphecy->reveal(),
            'event'
        );

        self::assertTrue(
            $this->session->hasObject($event)
        );
        self::assertSame(
            $event,
            $this->session->getObjectByIdentifier(12, Event::class)
        );
    }

    /**
     * @test
     */
    public function registerWithRepositoryWillAddObjectByUidToSession(): void
    {
        $event = new Event();
        $event->_setProperty('uid', 543);
        $event->setTitle('Test Event');

        $this->requestProphecy
            ->getArgument('event')
            ->shouldBeCalled()
            ->willReturn('543');

        $this->eventRepositoryProphecy
            ->findHiddenObject(543)
            ->shouldBeCalled()
            ->willReturn($event);

        $this->subject->registerHiddenObjectInExtbaseSession(
            $this->eventRepositoryProphecy->reveal(),
            $this->requestProphecy->reveal(),
            'event'
        );

        self::assertTrue(
            $this->session->hasObject($event)
        );
        self::assertSame(
            $event,
            $this->session->getObjectByIdentifier(543, Event::class)
        );
    }
}
