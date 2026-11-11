<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Domain\Finisher;

use JWeiland\Events2\Domain\Finisher\SaveEventFinisher;
use JWeiland\Events2\Helper\PathSegmentHelper;
use JWeiland\Events2\Service\DayRelationService;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use JWeiland\Events2\Tests\Functional\Traits\InsertEventTrait;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Form\Domain\Finishers\FinisherContext;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The SaveEventFinisher generates the path segment like the DataHandler:
 * before the INSERT of a new event and only for an empty path segment of an
 * edited event.
 */
class SaveEventFinisherPathSegmentTest extends FunctionalTestCase
{
    use InsertEventTrait;

    private const TABLE = 'tx_events2_domain_model_event';

    protected array $coreExtensionsToLoad = [
        'form',
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

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Events2PageTree.csv');
        $this->insertEvent(
            title: 'Weekly market',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            additionalFields: ['path_segment' => 'weekly-market'],
        );
    }

    #[Test]
    public function newEventWithExistingTitleIsInsertedWithIncrementedPathSegmentInOneInsert(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('insert')
            ->with(
                self::TABLE,
                self::callback(
                    static fn(array $data): bool => ($data['path_segment'] ?? '') === 'weekly-market-1',
                ),
            );
        $connection->method('lastInsertId')->willReturn('2');
        $connection->expects(self::never())->method('update');

        $subject = $this->getSubject(['mode' => 'insert'], connection: $connection);
        $databaseData = $this->addPathSegmentToEvent($subject, $this->getNewEventData());

        (new \ReflectionMethod($subject, 'saveToDatabase'))->invoke($subject, $databaseData, self::TABLE, 4);
    }

    #[Test]
    public function newEventIsStoredWithPathSegment(): void
    {
        $subject = $this->getSubject(['mode' => 'insert']);
        $databaseData = $this->addPathSegmentToEvent($subject, $this->getNewEventData('Flea market'));

        (new \ReflectionMethod($subject, 'saveToDatabase'))->invoke($subject, $databaseData, self::TABLE, 4);

        self::assertSame(
            ['weekly-market', 'flea-market'],
            $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE)
                ->select('path_segment')
                ->from(self::TABLE)
                ->orderBy('uid', 'ASC')
                ->executeQuery()
                ->fetchFirstColumn(),
        );
    }

    #[Test]
    public function editedEventKeepsPathSegmentOnChangedTitle(): void
    {
        $subject = $this->getSubject(['mode' => 'update', 'whereClause' => ['uid' => 1]], eventUidToUpdate: 1);

        self::assertSame(
            ['title' => 'Flea market'],
            $this->addPathSegmentToEvent($subject, ['title' => 'Flea market']),
        );
    }

    #[Test]
    public function editedEventWithEmptyPathSegmentGetsPathSegment(): void
    {
        $eventUid = $this->insertEvent(
            title: 'Weekly market',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
        );
        $subject = $this->getSubject(['mode' => 'update', 'whereClause' => ['uid' => $eventUid]], eventUidToUpdate: $eventUid);

        self::assertSame(
            ['teaser' => 'Fresh vegetables', 'path_segment' => 'weekly-market-1'],
            $this->addPathSegmentToEvent($subject, ['teaser' => 'Fresh vegetables']),
        );
    }

    #[Test]
    public function editedEventWithEmptyPathSegmentDoesNotCollideWithItself(): void
    {
        $eventUid = $this->insertEvent(
            title: 'Flea market',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
        );
        $subject = $this->getSubject(['mode' => 'update', 'whereClause' => ['uid' => $eventUid]], eventUidToUpdate: $eventUid);

        self::assertSame(
            ['title' => 'Flea market', 'path_segment' => 'flea-market'],
            $this->addPathSegmentToEvent($subject, ['title' => 'Flea market']),
        );
    }

    /**
     * The form updates the hidden column of a just inserted event in a
     * second step. There is no event UID in the URI of the form.
     */
    #[Test]
    public function updateOfNewEventWithoutEventUidInRequestKeepsData(): void
    {
        $subject = $this->getSubject(['mode' => 'update', 'whereClause' => ['uid' => 1]]);

        self::assertSame(
            ['hidden' => 1],
            $this->addPathSegmentToEvent($subject, ['hidden' => 1]),
        );
    }

    /**
     * @return array<string, int|string>
     */
    private function getNewEventData(string $title = 'Weekly market'): array
    {
        return [
            'pid' => Events2Constants::PAGE_STORAGE,
            'event_type' => 'single',
            'title' => $title,
        ];
    }

    /**
     * @param array<string, mixed> $databaseData
     * @return array<string, mixed>
     */
    private function addPathSegmentToEvent(SaveEventFinisher $subject, array $databaseData): array
    {
        return (new \ReflectionMethod($subject, 'addPathSegmentToEvent'))->invoke($subject, $databaseData);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function getSubject(array $options, int $eventUidToUpdate = 0, ?Connection $connection = null): SaveEventFinisher
    {
        $subject = new class (
            $this->get(ConnectionPool::class),
            $this->get(PathSegmentHelper::class),
            self::createStub(DayRelationService::class),
        ) extends SaveEventFinisher {
            public int $eventUidToUpdate = 0;

            public ?Connection $connection = null;

            protected function getEventUidToUpdate(): int
            {
                return $this->eventUidToUpdate;
            }

            protected function getConnectionForTable(string $table): Connection
            {
                return $this->connection ?? parent::getConnectionForTable($table);
            }
        };
        $subject->eventUidToUpdate = $eventUidToUpdate;
        $subject->connection = $connection;
        $subject->setOptions(['table' => self::TABLE] + $options);
        $subject->setFinisherIdentifier('SaveEventFinisher');

        $request = new Request(
            (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters()),
        );
        (new \ReflectionProperty($subject, 'finisherContext'))->setValue(
            $subject,
            new FinisherContext(self::createStub(FormRuntime::class), $request),
        );

        return $subject;
    }
}
