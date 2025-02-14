<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Helper;

use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Helper\Exception\NoUniquePathSegmentException;
use JWeiland\Events2\Helper\PathSegmentHelper;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test
 */
class PathSegmentHelperTest extends FunctionalTestCase
{
    protected ExtConf $extConf;

    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'sjbr/static-info-tables',
        'jweiland/events2',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/PathSegmentHelper.csv');

        $this->extConf = GeneralUtility::makeInstance(ExtConf::class);

        $this->subject = new PathSegmentHelper(
            GeneralUtility::makeInstance(EventDispatcher::class),
            GeneralUtility::makeInstance(PersistenceManagerInterface::class),
            $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_event'),
            $this->extConf,
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
        );

        parent::tearDown();
    }

    public static function getPathSegmentTypesSlugTypes(): array
    {
        return [
            'empty' => ['empty', 'default-'],
            'uid' => ['uid', 'default-'],
            'realurl' => ['realurl', ''],
        ];
    }

    /**
     * @test
     * @dataProvider getPathSegmentTypesSlugTypes
     */
    public function generatePathSegmentWithEmptyBaseRecordWillGenerateDefaultSlug(string $pathSegmentType, $expectedPrefix): void
    {
        $this->extConf->setPathSegmentType($pathSegmentType);

        if ($pathSegmentType === 'realurl') {
            $this->expectException(NoUniquePathSegmentException::class);
        }

        self::assertStringStartsWith(
            $expectedPrefix,
            $this->subject->generatePathSegment([]),
        );
    }

    /**
     * @test
     * @dataProvider getPathSegmentTypesSlugTypes
     */
    public function generatePathSegmentWithMissingRecordUidWillGenerateDefaultSlug(string $pathSegmentType, $expectedPrefix): void
    {
        $this->extConf->setPathSegmentType($pathSegmentType);

        if ($pathSegmentType === 'realurl') {
            $this->expectException(NoUniquePathSegmentException::class);
        }

        self::assertStringStartsWith(
            $expectedPrefix,
            $this->subject->generatePathSegment([
                'pid' => 11,
            ]),
        );
    }

    /**
     * @test
     * @dataProvider getPathSegmentTypesSlugTypes
     */
    public function generatePathSegmentWithEmptyRecordUidWillGenerateDefaultSlug(string $pathSegmentType, $expectedPrefix): void
    {
        $this->extConf->setPathSegmentType($pathSegmentType);

        if ($pathSegmentType === 'realurl') {
            $this->expectException(NoUniquePathSegmentException::class);
        }

        self::assertStringStartsWith(
            $expectedPrefix,
            $this->subject->generatePathSegment([
                'uid' => 0,
                'pid' => 11,
            ]),
        );
    }

    /**
     * @test
     */
    public function generatePathSegmentWithTypeUidWillGenerateSlug(): void
    {
        $this->extConf->setPathSegmentType('uid');

        self::assertSame(
            'hello-typo3-134',
            $this->subject->generatePathSegment([
                'uid' => 134,
                'title' => 'Hello TYPO3',
                'pid' => 11,
            ]),
        );
    }

    /**
     * @test
     */
    public function generatePathSegmentWithTypeRealurlWillGenerateSlug(): void
    {
        $this->extConf->setPathSegmentType('realurl');

        self::assertSame(
            'hello-typo3',
            $this->subject->generatePathSegment([
                'uid' => 2,
                'title' => 'Hello TYPO3',
                'pid' => 11,
            ]),
        );
    }

    /**
     * @test
     */
    public function generatePathSegmentWithTypeRealurlWillGenerateSlugWithIncrement(): void
    {
        $this->extConf->setPathSegmentType('realurl');

        self::assertSame(
            'weekmarket-1',
            $this->subject->generatePathSegment([
                'uid' => 2,
                'title' => 'Weekmarket',
                'pid' => 11,
            ]),
        );
    }

    /**
     * @test
     */
    public function updatePathSegmentForEventUpdatesPathSegment(): void
    {
        $this->extConf->setPathSegmentType('uid');

        $event = new Event();
        $event->setEventType('simple');
        $event->setPid(11);
        $event->setTitle('Gaming');
        $event->setEventBegin(new \DateTimeImmutable('now'));

        $this->subject->updatePathSegmentForEvent($event);

        // We already have one record in DB, so next increment/UID is 2
        self::assertSame(
            'gaming-2',
            $event->getPathSegment(),
        );
    }
}
