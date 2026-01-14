<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Service;

use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Service\CacheService;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\CacheDataCollector;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case.
 */
class CacheServiceTest extends FunctionalTestCase
{
    protected CacheService $cacheService;

    protected CacheDataCollector $cacheDataCollector;

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

        $this->cacheDataCollector = GeneralUtility::makeInstance(CacheDataCollector::class);

        $request = new ServerRequest('https://www.example.com', 'GET');
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $GLOBALS['TYPO3_REQUEST'] = $request->withAttribute('frontend.cache.collector', $this->cacheDataCollector);

        $this->cacheService = new CacheService();
    }

    protected function tearDown(): void
    {
        unset(
            $this->cacheDataCollector,
            $this->cacheService,
        );

        parent::tearDown();
    }

    #[Test]
    public function addCacheTagsWithEmptyArrayWillNotAddCacheTags(): void
    {
        $this->cacheService->addCacheTagsByEventRecords([]);

        $cacheTags = [];
        foreach ($this->cacheDataCollector->getCacheTags() as $cacheTag) {
            $cacheTags[] = $cacheTag->name;
        }

        self::assertSame(
            [],
            $cacheTags,
        );
    }

    #[Test]
    public function addCacheTagsWithEmptyQueryResultWillNotAddCacheTags(): void
    {
        $persistenceManagerMock = self::createStub(PersistenceManager::class);

        $dataMapperMock = $this->createMock(DataMapper::class);
        $dataMapperMock
            ->expects(self::once())
            ->method('map')
            ->willReturn([]);

        $queryMock = $this->createMock(Query::class);
        $queryMock
            ->expects(self::once())
            ->method('getType')
            ->willReturn(Event::class);

        $queryResult = new QueryResult($dataMapperMock, $persistenceManagerMock);
        $queryResult->setQuery($queryMock);

        $this->cacheService->addCacheTagsByEventRecords($queryResult);

        $cacheTags = [];
        foreach ($this->cacheDataCollector->getCacheTags() as $cacheTag) {
            $cacheTags[] = $cacheTag->name;
        }

        self::assertSame(
            [],
            $cacheTags,
        );
    }

    #[Test]
    public function addCacheTagsWithEventArrayWillCreateCacheTags(): void
    {
        $event1 = new Event();
        $event1->_setProperty('uid', 1);
        $event3 = new Event();
        $event3->_setProperty('uid', 3);
        $event3->_setProperty('_localizedUid', 2);

        $this->cacheService->addCacheTagsByEventRecords([
            0 => $event1,
            1 => $event3,
        ]);

        $cacheTags = [];
        foreach ($this->cacheDataCollector->getCacheTags() as $cacheTag) {
            $cacheTags[] = $cacheTag->name;
        }

        self::assertSame(
            [
                'tx_events2_uid_1',
                'tx_events2_uid_3',
                'tx_events2_uid_2',
            ],
            $cacheTags,
        );
    }

    #[Test]
    public function addPageCacheTagsWithEmptyArrayWillAddTableAsCacheTag(): void
    {
        $querySettings = $this->get(Typo3QuerySettings::class);
        $querySettings->setStoragePageIds([]);

        $query = $this->get(Query::class);
        $query->setQuerySettings($querySettings);

        $this->cacheService->addPageCacheTagsByQuery($query);

        $cacheTags = [];
        foreach ($this->cacheDataCollector->getCacheTags() as $cacheTag) {
            $cacheTags[] = $cacheTag->name;
        }

        self::assertSame(
            [
                'tx_events2_domain_model_event',
            ],
            $cacheTags,
        );
    }

    #[Test]
    public function addPageCacheTagsWithFileldArrayWillAddStoragePidsAsCacheTags(): void
    {
        $querySettings = $this->get(Typo3QuerySettings::class);
        $querySettings->setStoragePageIds([12, 13]);

        $query = $this->get(Query::class);
        $query->setQuerySettings($querySettings);

        $this->cacheService->addPageCacheTagsByQuery($query);

        $cacheTags = [];
        foreach ($this->cacheDataCollector->getCacheTags() as $cacheTag) {
            $cacheTags[] = $cacheTag->name;
        }

        self::assertSame(
            [
                'tx_events2_pid_12',
                'tx_events2_pid_13',
            ],
            $cacheTags,
        );
    }
}
