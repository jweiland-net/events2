<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Upgrade;

use JWeiland\Events2\Helper\PathSegmentHelper;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use JWeiland\Events2\Upgrade\EventsSlugUpgrade;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case for class \JWeiland\Events2\Upgrade\EventsSlugUpgrade
 */
class EventsSlugUpgradeTest extends FunctionalTestCase
{
    private EventsSlugUpgrade $subject;

    protected array $coreExtensionsToLoad = [
        'install',
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
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

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/EventsSlugUpgrade.csv');

        $this->subject = new EventsSlugUpgrade(
            $this->get(PathSegmentHelper::class),
            $this->getConnectionPool(),
        );
    }

    protected function tearDown(): void
    {
        unset($this->subject);

        parent::tearDown();
    }

    #[Test]
    public function updateNecessaryWithEmptyPathSegmentReturnsTrue(): void
    {
        self::assertTrue($this->subject->updateNecessary());
    }

    #[Test]
    public function executeUpdateFillsEmptyPathSegmentsAndKeepsExistingOnes(): void
    {
        self::assertTrue($this->subject->executeUpdate());

        self::assertSame(
            [
                1 => 'weekmarket',
                2 => 'weekmarket-1',
                3 => 'weekmarket-2',
                4 => 'my-own-slug',
                5 => '',
            ],
            $this->getPathSegmentsByUid(),
        );
    }

    #[Test]
    public function updateNecessaryAfterExecuteUpdateReturnsFalse(): void
    {
        $this->subject->executeUpdate();

        self::assertFalse($this->subject->updateNecessary());
    }

    /**
     * @return array<int, string>
     */
    private function getPathSegmentsByUid(): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_event');
        $queryBuilder->getRestrictions()->removeAll();
        $pathSegments = [];
        $queryResult = $queryBuilder
            ->select('uid', 'path_segment')
            ->from('tx_events2_domain_model_event')
            ->orderBy('uid', 'ASC')
            ->executeQuery();
        while ($eventRecord = $queryResult->fetchAssociative()) {
            $pathSegments[(int)$eventRecord['uid']] = (string)$eventRecord['path_segment'];
        }

        return $pathSegments;
    }
}
