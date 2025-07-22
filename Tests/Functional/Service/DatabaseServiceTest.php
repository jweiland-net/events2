<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Service;

use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Service\DatabaseService;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use JWeiland\Events2\Tests\Functional\Traits\InsertEventTrait;
use JWeiland\Events2\Utility\DateTimeUtility;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for DatabaseService
 */
class DatabaseServiceTest extends FunctionalTestCase
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

    protected function setUp(): void
    {
        parent::setUp();

        $eventBegin = new \DateTimeImmutable('first day of this month midnight');
        $eventBegin = $eventBegin
            ->modify('+4 days')
            ->modify('-2 months');

        $this->insertEvent(
            title: 'Week market',
            eventBegin: $eventBegin,
            additionalFields: [
                'event_type' => 'recurring',
                'xth' => 31,
                'weekday' => 16,
            ],
            organizer: 'Stefan',
            location: 'Market',
        );

        $this->createDayRelations();
    }

    #[Test]
    public function getDaysInRangeWillFindDaysForCurrentMonth(): void
    {
        $eventBegin = new \DateTimeImmutable('first day of this month midnight');
        $eventEnd = new \DateTimeImmutable('last day of this month midnight');

        $databaseService = new DatabaseService(
            new ExtConf(
                recurringPast: 3,
                recurringFuture: 6,
            ),
            new DateTimeUtility(),
        );

        $days = $databaseService->getDaysInRange($eventBegin, $eventEnd, [Events2Constants::PAGE_STORAGE]);

        self::assertGreaterThanOrEqual(
            3,
            count($days),
        );
    }
}
