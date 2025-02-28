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
use JWeiland\Events2\Helper\Exception\NoUniquePathSegmentException;
use JWeiland\Events2\Helper\PathSegmentHelper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test
 */
class PathSegmentHelperTest extends FunctionalTestCase
{
    protected SlugHelper|MockObject $slugHelperMock;

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

        $this->slugHelperMock = $this->createMock(SlugHelper::class);
    }

    protected function tearDown(): void
    {
        unset(
            $this->slugHelperMock,
        );

        parent::tearDown();
    }

    #[Test]
    public function generatePathSegmentWithEmptyBaseRecordThrowsException(): void
    {
        $this->slugHelperMock
            ->expects(self::once())
            ->method('generate')
            ->with(
                self::identicalTo([]),
                self::identicalTo(0),
            )
            ->willReturn('');

        GeneralUtility::addInstance(SlugHelper::class, $this->slugHelperMock);

        $this->expectException(NoUniquePathSegmentException::class);

        $subject = $this->getSubject(
            new ExtConf(pathSegmentType: 'uid'),
        );

        $subject->generatePathSegment([]);
    }

    #[Test]
    public function generatePathSegmentWillReturnSlug(): void
    {
        $baseRecord = [
            'uid' => 2,
            'pid' => 12,
            'title' => 'Weekly market',
        ];

        $this->slugHelperMock
            ->expects(self::once())
            ->method('generate')
            ->with(
                self::identicalTo($baseRecord),
                self::identicalTo(12),
            )
            ->willReturn('weekly-market-2');

        GeneralUtility::addInstance(SlugHelper::class, $this->slugHelperMock);

        $subject = $this->getSubject(
            new ExtConf(pathSegmentType: 'uid'),
        );

        self::assertSame(
            'weekly-market-2',
            $subject->generatePathSegment($baseRecord),
        );
    }

    protected function getSubject(ExtConf $extConf): PathSegmentHelper
    {
        return new PathSegmentHelper(
            GeneralUtility::makeInstance(EventDispatcher::class),
            GeneralUtility::makeInstance(PersistenceManagerInterface::class),
            $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_event'),
            $extConf,
        );
    }
}
