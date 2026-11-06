<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Service\Record;

use JWeiland\Events2\Service\Record\ExceptionRecordService;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use JWeiland\Events2\Tests\Functional\Traits\InsertEventTrait;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case for class \JWeiland\Events2\Service\Record\ExceptionRecordService
 */
class ExceptionRecordServiceTest extends FunctionalTestCase
{
    use InsertEventTrait;

    protected array $coreExtensionsToLoad = [
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

        unset($GLOBALS['BE_USER']);
    }

    #[Test]
    public function findAllByEventUidWithoutBackendUserReturnsExceptionRecords(): void
    {
        $eventUid = $this->insertEventWithException();

        $exceptionRecords = $this->get(ExceptionRecordService::class)->findAllByEventUid($eventUid);

        self::assertCount(1, $exceptionRecords);
        self::assertSame('Add', current($exceptionRecords)['exception_type']);
    }

    #[Test]
    public function findAllByEventUidWithNegativeWorkspaceIdReturnsLiveExceptionRecords(): void
    {
        $eventUid = $this->insertEventWithException();
        $this->get(Context::class)->setAspect('workspace', new WorkspaceAspect(-99));

        $exceptionRecords = $this->get(ExceptionRecordService::class)->findAllByEventUid($eventUid);

        self::assertCount(1, $exceptionRecords);
    }

    private function insertEventWithException(): int
    {
        $eventUid = $this->insertEvent(
            title: 'Event with exception',
            eventBegin: new \DateTimeImmutable('midnight'),
            additionalFields: [
                'exceptions' => 1,
            ],
        );

        $this->getConnectionPool()
            ->getConnectionForTable('tx_events2_domain_model_exception')
            ->insert(
                'tx_events2_domain_model_exception',
                [
                    'pid' => Events2Constants::PAGE_STORAGE,
                    'event' => $eventUid,
                    'exception_type' => 'Add',
                    'exception_date' => (int)(new \DateTimeImmutable('tomorrow midnight'))->format('U'),
                ],
            );

        return $eventUid;
    }
}
