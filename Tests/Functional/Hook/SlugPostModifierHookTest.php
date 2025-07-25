<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Hook;

use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Event\GeneratePathSegmentEvent;
use JWeiland\Events2\Hook\SlugPostModifierHook;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\DataHandling\Model\RecordState;
use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for DayHelper
 */
class SlugPostModifierHookTest extends FunctionalTestCase
{
    protected EventDispatcherInterface|MockObject $eventDispatcherMock;

    protected SlugHelper|MockObject $slugHelperMock;

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'phpTimeZone' => Events2Constants::PHP_TIMEZONE,
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventDispatcherMock = $this->createMock(EventDispatcher::class);
        $this->slugHelperMock = $this->createMock(SlugHelper::class);
    }

    protected function tearDown(): void
    {
        unset(
            $this->slugHelperMock,
        );

        parent::tearDown();
    }

    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'sjbr/static-info-tables',
        'jweiland/events2',
    ];

    #[Test]
    public function modifyWithEmptyTableNameWillReturnOriginalSlug(): void
    {
        $subject = $this->getSubject(new ExtConf());
        $parameters = [
            'slug' => 'hello-world',
        ];

        self::assertSame(
            'hello-world',
            $subject->modify($parameters, $this->slugHelperMock),
        );
    }

    #[Test]
    public function modifyWithEmptyFieldNameWillReturnOriginalSlug(): void
    {
        $subject = $this->getSubject(new ExtConf());
        $parameters = [
            'slug' => 'hello-world',
            'tableName' => '',
        ];

        self::assertSame(
            'hello-world',
            $subject->modify($parameters, $this->slugHelperMock),
        );
    }

    #[Test]
    public function modifyWithInvalidTableNameWillReturnOriginalSlug(): void
    {
        $subject = $this->getSubject(new ExtConf());
        $parameters = [
            'slug' => 'hello-world',
            'tableName' => 'tt_address',
            'fieldName' => 'path_segment',
        ];

        self::assertSame(
            'hello-world',
            $subject->modify($parameters, $this->slugHelperMock),
        );
    }

    #[Test]
    public function modifyWithInvalidFieldNameWillReturnOriginalSlug(): void
    {
        $subject = $this->getSubject(new ExtConf());
        $parameters = [
            'slug' => 'hello-world',
            'tableName' => 'tx_events2_domain_model_event',
            'fieldName' => 'slug',
        ];

        self::assertSame(
            'hello-world',
            $subject->modify($parameters, $this->slugHelperMock),
        );
    }

    #[Test]
    public function modifyWithUidWillReturnSlugWithUid(): void
    {
        $subject = $this->getSubject(new ExtConf(pathSegmentType: 'uid'));

        // uid is already appended when coming from SlugHelper
        $parameters = [
            'slug' => 'hello-world-1',
            'tableName' => 'tx_events2_domain_model_event',
            'fieldName' => 'path_segment',
        ];

        self::assertSame(
            'hello-world-1',
            $subject->modify($parameters, $this->slugHelperMock),
        );
    }

    #[Test]
    public function modifyWithRealurlAndMissingRecordWillReturnEmptySlug(): void
    {
        $subject = $this->getSubject(new ExtConf(pathSegmentType: 'realurl'));

        $parameters = [
            'slug' => 'hello-world',
            'tableName' => 'tx_events2_domain_model_event',
            'fieldName' => 'path_segment',
        ];

        $this->slugHelperMock
            ->expects(self::never())
            ->method('buildSlugForUniqueInTable');

        self::assertSame(
            '',
            $subject->modify($parameters, $this->slugHelperMock),
        );
    }

    #[Test]
    public function modifyWithRealurlWillReturnSlugInRealurlType(): void
    {
        $subject = $this->getSubject(new ExtConf(pathSegmentType: 'realurl'));

        $parameters = [
            'slug' => 'hello-world',
            'tableName' => 'tx_events2_domain_model_event',
            'fieldName' => 'path_segment',
            'pid' => Events2Constants::PAGE_STORAGE,
            'record' => [
                'uid' => 2,
            ],
        ];

        $this->slugHelperMock
            ->expects(self::once())
            ->method('buildSlugForUniqueInTable')
            ->with(
                self::identicalTo($parameters['slug']),
                self::isInstanceOf(RecordState::class),
            )
            ->willReturn('hello-world-1');

        self::assertSame(
            'hello-world-1',
            $subject->modify($parameters, $this->slugHelperMock),
        );
    }

    #[Test]
    public function modifyWithDefaultWillReturnSlugFromEventListener(): void
    {
        $subject = $this->getSubject(new ExtConf(pathSegmentType: 'empty'));

        $parameters = [
            'slug' => 'hello-world',
            'tableName' => 'tx_events2_domain_model_event',
            'fieldName' => 'path_segment',
            'pid' => Events2Constants::PAGE_STORAGE,
            'record' => [
                'uid' => 2,
            ],
        ];

        $generatePathSegmentHelper = new GeneratePathSegmentEvent($parameters, $this->slugHelperMock);
        $generatePathSegmentHelper->setPathSegment('another-slug');

        $this->eventDispatcherMock
            ->expects(self::once())
            ->method('dispatch')
            ->with(
                self::isInstanceOf(GeneratePathSegmentEvent::class),
            )
            ->willReturn($generatePathSegmentHelper);

        self::assertSame(
            'another-slug',
            $subject->modify($parameters, $this->slugHelperMock),
        );
    }

    private function getSubject(ExtConf $extConf): SlugPostModifierHook
    {
        return new SlugPostModifierHook(
            $this->eventDispatcherMock,
            $extConf,
            $this->createMock(Logger::class),
        );
    }
}
