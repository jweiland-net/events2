<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Service;

use JWeiland\Events2\Service\DayRecordBuilderService;
use JWeiland\Events2\Service\Record\TimeRecordService;
use JWeiland\Events2\Service\Result\DayGeneratorResult;
use JWeiland\Events2\Service\TimeService;
use JWeiland\Events2\Utility\DateTimeUtility;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case.
 */
class TimeServiceTest extends FunctionalTestCase
{
    protected TimeService $timeService;

    protected TimeRecordService|MockObject $timeRecordServiceMock;

    protected DayRecordBuilderService|MockObject $dayRecordBuilderServiceMock;

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

        $this->timeRecordServiceMock = $this->createMock(TimeRecordService::class);
        $this->dayRecordBuilderServiceMock = $this->createMock(DayRecordBuilderService::class);

        $this->timeService = new TimeService(
            $this->timeRecordServiceMock,
            $this->dayRecordBuilderServiceMock,
            new DateTimeUtility(),
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->timeRecordServiceMock,
            $this->dayRecordBuilderServiceMock,
            $this->timeService,
        );

        parent::tearDown();
    }

    #[Test]
    public function enrich(): void
    {
        self::markTestSkipped('unfinished');
    }
}
