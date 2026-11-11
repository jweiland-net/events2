<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Importer;

use JWeiland\Events2\Domain\Model\Category;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Organizer;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Importer\EventRecordBuilder;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for EventRecordBuilder
 */
class EventRecordBuilderTest extends FunctionalTestCase
{
    private const SCALAR_COLUMNS = [
        'uid',
        'pid',
        'sys_language_uid',
        'l10n_parent',
        'hidden',
        'starttime',
        'endtime',
        'event_type',
        'top_of_list',
        'title',
        'path_segment',
        'teaser',
        'event_begin',
        'event_end',
        'recurring_end',
        'same_day',
        'xth',
        'weekday',
        'each_weeks',
        'each_months',
        'detail_information',
        'attendance_mode',
        'free_entry',
        'import_id',
        'location',
    ];

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

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/EventRecordBuilder.csv');
    }

    #[Test]
    public function buildRecordOfStoredEventReturnsValuesOfDatabaseRecord(): void
    {
        $event = $this->get(EventRepository::class)->findByIdentifier(1);
        self::assertInstanceOf(Event::class, $event);

        $record = $this->get(EventRecordBuilder::class)->buildRecord($event);
        $storedRecord = $this->getConnectionPool()
            ->getConnectionForTable('tx_events2_domain_model_event')
            ->select(['*'], 'tx_events2_domain_model_event', ['uid' => 1])
            ->fetchAssociative();

        foreach (self::SCALAR_COLUMNS as $column) {
            self::assertEquals($storedRecord[$column], $record[$column], 'Column: ' . $column);
        }
    }

    #[Test]
    public function buildRecordOfStoredEventSkipsNotLoadedLazyRelations(): void
    {
        $event = $this->get(EventRepository::class)->findByIdentifier(1);
        self::assertInstanceOf(Event::class, $event);

        $record = $this->get(EventRecordBuilder::class)->buildRecord($event);

        self::assertArrayNotHasKey('organizers', $record);
        self::assertArrayNotHasKey('categories', $record);
    }

    #[Test]
    public function buildRecordOfNewEventReturnsRecordWithoutUid(): void
    {
        $event = new Event();
        $event->setPid(Events2Constants::PAGE_STORAGE);
        $event->setTitle('Weekly market');
        $event->setEventBegin(new \DateTimeImmutable('2024-05-10 00:00:00'));

        $record = $this->get(EventRecordBuilder::class)->buildRecord($event);

        self::assertArrayNotHasKey('uid', $record);
        self::assertSame(Events2Constants::PAGE_STORAGE, $record['pid']);
        self::assertSame('Weekly market', $record['title']);
        self::assertSame(1715292000, $record['event_begin']);
        self::assertSame(0, $record['event_end']);
        self::assertSame(0, $record['location']);
    }

    #[Test]
    public function buildRecordReturnsCommaSeparatedUidsOfPersistedRelations(): void
    {
        $event = new Event();
        $event->setOrganizers($this->getObjectStorage(Organizer::class, 2, 5));
        $event->setCategories($this->getObjectStorage(Category::class, 3));
        // Not persisted organizer without UID
        $event->addOrganizer(new Organizer());

        $record = $this->get(EventRecordBuilder::class)->buildRecord($event);

        self::assertSame('2,5', $record['organizers']);
        self::assertSame('3', $record['categories']);
    }

    /**
     * @param class-string<Organizer|Category> $className
     */
    private function getObjectStorage(string $className, int ...$uids): ObjectStorage
    {
        $objectStorage = new ObjectStorage();
        foreach ($uids as $uid) {
            $domainObject = new $className();
            $domainObject->_setProperty('uid', $uid);
            $objectStorage->attach($domainObject);
        }

        return $objectStorage;
    }
}
