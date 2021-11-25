<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Utility;

use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Utility\CacheUtility;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Service\EnvironmentService;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 */
class CacheUtilityTest extends UnitTestCase
{
    protected CacheUtility $subject;

    protected function setUp(): void
    {
        /** @var EnvironmentService|ObjectProphecy $environmentServiceProphecy */
        $environmentServiceProphecy = $this->prophesize(EnvironmentService::class);
        $environmentServiceProphecy
            ->isEnvironmentInFrontendMode()
            ->shouldBeCalled()
            ->willReturn(true);
        GeneralUtility::setSingletonInstance(EnvironmentService::class, $environmentServiceProphecy->reveal());

        $tsfeProphecy = $this->prophesize(TypoScriptFrontendController::class);
        $GLOBALS['TSFE'] = $tsfeProphecy->reveal();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TSFE']);
    }

    /**
     * @test
     */
    public function addCacheTagsByEventRecordsWithoutEventsWillNotAddCacheTags(): void
    {
        $tsfeProphecy = $this->prophesize(TypoScriptFrontendController::class);
        $tsfeProphecy
            ->addCacheTags(Argument::any())
            ->shouldNotBeCalled();

        $GLOBALS['TSFE'] = $tsfeProphecy->reveal();

        CacheUtility::addCacheTagsByEventRecords([]);
    }

    /**
     * @test
     */
    public function addCacheTagsByEventRecordsWithEventsWillAddCacheTags(): void
    {
        $event = new Event();
        $event->_setProperty('uid', 123);

        $tsfeProphecy = $this->prophesize(TypoScriptFrontendController::class);
        $tsfeProphecy
            ->addCacheTags(['tx_events2_uid_123'])
            ->shouldBeCalled();

        $GLOBALS['TSFE'] = $tsfeProphecy->reveal();

        CacheUtility::addCacheTagsByEventRecords([$event]);
    }

    /**
     * @test
     */
    public function addCacheTagsByEventRecordsWithLocalizedEventsWillAddCacheTags(): void
    {
        $event = new Event();
        $event->_setProperty('uid', 123);
        $event->_setProperty('_localizedUid', 321);

        $tsfeProphecy = $this->prophesize(TypoScriptFrontendController::class);
        $tsfeProphecy
            ->addCacheTags(['tx_events2_uid_123', 'tx_events2_uid_321'])
            ->shouldBeCalled();

        $GLOBALS['TSFE'] = $tsfeProphecy->reveal();

        CacheUtility::addCacheTagsByEventRecords([$event]);
    }

    /**
     * @test
     */
    public function addPageCacheTagsByQueryWithoutStoragePidsWillAddTableNameAsCacheTag(): void
    {
        $tsfeProphecy = $this->prophesize(TypoScriptFrontendController::class);
        $tsfeProphecy
            ->addCacheTags(['tx_events2_domain_model_event'])
            ->shouldBeCalled();

        $GLOBALS['TSFE'] = $tsfeProphecy->reveal();

        /** @var QuerySettingsInterface|ObjectProphecy $querySettingsProphecy */
        $querySettingsProphecy = $this->prophesize(Typo3QuerySettings::class);
        /** @var QueryInterface|ObjectProphecy $queryProphecy */
        $queryProphecy = $this->prophesize(Query::class);
        $queryProphecy
            ->getQuerySettings()
            ->shouldBeCalled()
            ->willReturn($querySettingsProphecy->reveal());

        CacheUtility::addPageCacheTagsByQuery($queryProphecy->reveal());
    }

    /**
     * @test
     */
    public function addPageCacheTagsByQueryWithStoragePidsWillAddStoragePidCacheTags(): void
    {
        $tsfeProphecy = $this->prophesize(TypoScriptFrontendController::class);
        $tsfeProphecy
            ->addCacheTags(['tx_events2_pid_123', 'tx_events2_pid_234'])
            ->shouldBeCalled();

        $GLOBALS['TSFE'] = $tsfeProphecy->reveal();

        /** @var QuerySettingsInterface|ObjectProphecy $querySettingsProphecy */
        $querySettingsProphecy = $this->prophesize(Typo3QuerySettings::class);
        $querySettingsProphecy
            ->getStoragePageIds()
            ->shouldBeCalled()
            ->willReturn([123, 234]);
        /** @var QueryInterface|ObjectProphecy $queryProphecy */
        $queryProphecy = $this->prophesize(Query::class);
        $queryProphecy
            ->getQuerySettings()
            ->shouldBeCalled()
            ->willReturn($querySettingsProphecy->reveal());

        CacheUtility::addPageCacheTagsByQuery($queryProphecy->reveal());
    }
}
