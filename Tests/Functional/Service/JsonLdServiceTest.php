<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Service;

use JWeiland\Events2\Domain\Factory\TimeFactory;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Link;
use JWeiland\Events2\Domain\Model\Location;
use JWeiland\Events2\Domain\Model\Organizer;
use JWeiland\Events2\Domain\Model\Time;
use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Service\DayRelationService;
use JWeiland\Events2\Service\JsonLdService;
use JWeiland\Events2\Utility\DateTimeUtility;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for JsonLdService
 */
class JsonLdServiceTest extends FunctionalTestCase
{
    protected DayRepository $dayRepository;

    protected QuerySettingsInterface $querySettings;

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

        $GLOBALS['BE_USER'] = new BackendUserAuthentication();

        $request = new ServerRequest('https://www.example.com/typo3', 'GET');
        $GLOBALS['TYPO3_REQUEST'] = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);

        $this->querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $this->querySettings->setStoragePageIds([11, 40]);

        $this->dayRepository = GeneralUtility::makeInstance(DayRepository::class);
        $this->dayRepository->setDefaultQuerySettings($this->querySettings);

        $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
        $dayRelationService = GeneralUtility::makeInstance(DayRelationService::class);

        $eventRepository = GeneralUtility::makeInstance(EventRepository::class);
        $eventRepository->setDefaultQuerySettings($this->querySettings);
        $eventRepository->setDefaultOrderings([
            'uid' => QueryInterface::ORDER_ASCENDING,
        ]);

        $link = new Link();
        $link->setTitle('TYPO3');
        $link->setLink('https://www.typo3.org');

        $organizer = new Organizer();
        $organizer->setPid(11);
        $organizer->setOrganizer('Stefan');
        $organizer->setLink($link);

        $location = new Location();
        $location->setPid(11);
        $location->setLocation('jweiland.net');
        $location->setStreet('Echterdinger Straße');
        $location->setHouseNumber('57');
        $location->setZip('70794');
        $location->setCity('Filderstadt');

        $eventBegin = new \DateTimeImmutable('first day of this month midnight');
        $eventBegin = $eventBegin
            ->modify('+4 days')
            ->modify('-2 months');
        $eventEnd = $eventBegin->modify('+1 day');

        $event = GeneralUtility::makeInstance(Event::class);
        $event->setPid(11);
        $event->setEventType('duration');
        $event->setTopOfList(false);
        $event->setTitle('Week market');
        $event->setTeaser('');
        $event->setEventBegin($eventBegin);
        $event->setEventEnd($eventEnd);
        $event->setFreeEntry(true);
        $event->addOrganizer($organizer);
        $event->setLocation($location);
        $persistenceManager->add($event);

        $time = new Time();
        $time->setTimeBegin('08:00');
        $time->setTimeEntry('07:00');
        $time->setDuration('02:00');
        $time->setTimeEnd('10:00');

        $event = GeneralUtility::makeInstance(Event::class);
        $event->setPid(11);
        $event->setEventType('single');
        $event->setTopOfList(false);
        $event->setTitle('Birthday');
        $event->setDetailInformation('Happy birthday to you, happy birthday to you, ...');
        $event->setEventBegin($eventBegin);
        $event->setEventTime($time);
        $event->setTicketLink($link);
        $event->setFreeEntry(false);
        $event->addOrganizer($organizer);
        $event->setLocation($location);
        $persistenceManager->add($event);

        $persistenceManager->persistAll();

        $events = $eventRepository->findAll();
        foreach ($events as $event) {
            $dayRelationService->createDayRelations($event->getUid());
        }
    }

    protected function tearDown(): void
    {
        unset(
            $this->dayRepository,
        );

        parent::tearDown();
    }

    #[Test]
    public function addJsonLdAddsEventBeginDate(): void
    {
        $day = $this->dayRepository->findByIdentifier(1);

        $jsonLdService = new JsonLdService(new TimeFactory(new DateTimeUtility()));
        $jsonLdService->addJsonLdToPageHeader($day);

        self::assertSame(
            $day->getEvent()->getEventBegin()->format('Y-m-d'),
            $jsonLdService->getCollectedJsonLdData()['startDate'],
        );
    }

    #[Test]
    public function addJsonLdAddsEventEndDate(): void
    {
        $day = $this->dayRepository->findByIdentifier(1);

        $jsonLdService = new JsonLdService(new TimeFactory(new DateTimeUtility()));
        $jsonLdService->addJsonLdToPageHeader($day);

        self::assertSame(
            $day->getEvent()->getEventEnd()->format('Y-m-d'),
            $jsonLdService->getCollectedJsonLdData()['endDate'],
        );
    }

    #[Test]
    public function addJsonLdAddsTimeStartDate(): void
    {
        $day = $this->dayRepository->findByIdentifier(3);

        $jsonLdService = new JsonLdService(new TimeFactory(new DateTimeUtility()));
        $jsonLdService->addJsonLdToPageHeader($day);

        self::assertSame(
            $day->getEvent()->getEventTime()->getTimeBeginAsDateTime()->format('Y-m-d\TH:i:s'),
            $jsonLdService->getCollectedJsonLdData()['startDate'],
        );
    }

    #[Test]
    public function addJsonLdAddsTimeEntryDate(): void
    {
        $day = $this->dayRepository->findByIdentifier(3);

        $jsonLdService = new JsonLdService(new TimeFactory(new DateTimeUtility()));
        $jsonLdService->addJsonLdToPageHeader($day);

        self::assertSame(
            $day->getEvent()->getEventTime()->getTimeEntryAsDateTime()->format('Y-m-d\TH:i:s'),
            $jsonLdService->getCollectedJsonLdData()['doorTime'],
        );
    }

    #[Test]
    public function addJsonLdAddsDuration(): void
    {
        $day = $this->dayRepository->findByIdentifier(3);

        $jsonLdService = new JsonLdService(new TimeFactory(new DateTimeUtility()));
        $jsonLdService->addJsonLdToPageHeader($day);

        [$hours, $minutes] = GeneralUtility::trimExplode(
            ':',
            $day->getEvent()->getEventTime()->getDuration(),
        );

        self::assertSame(
            'PT' . (int)$hours . 'H' . (int)$minutes . 'M',
            $jsonLdService->getCollectedJsonLdData()['duration'],
        );
    }

    #[Test]
    public function addJsonLdAddsTimeEndDate(): void
    {
        $day = $this->dayRepository->findByIdentifier(3);

        $jsonLdService = new JsonLdService(new TimeFactory(new DateTimeUtility()));
        $jsonLdService->addJsonLdToPageHeader($day);

        self::assertSame(
            $day->getEvent()->getEventTime()->getTimeEndAsDateTime()->format('Y-m-d\TH:i:s'),
            $jsonLdService->getCollectedJsonLdData()['endDate'],
        );
    }

    #[Test]
    public function addJsonLdAddsEventTitle(): void
    {
        $day = $this->dayRepository->findByIdentifier(3);

        $jsonLdService = new JsonLdService(new TimeFactory(new DateTimeUtility()));
        $jsonLdService->addJsonLdToPageHeader($day);

        self::assertSame(
            $day->getEvent()->getTitle(),
            $jsonLdService->getCollectedJsonLdData()['name'],
        );
    }

    #[Test]
    public function addJsonLdAddsEventDescription(): void
    {
        $day = $this->dayRepository->findByIdentifier(3);

        $jsonLdService = new JsonLdService(new TimeFactory(new DateTimeUtility()));
        $jsonLdService->addJsonLdToPageHeader($day);

        self::assertSame(
            $day->getEvent()->getDetailInformation(),
            $jsonLdService->getCollectedJsonLdData()['description'],
        );
    }

    #[Test]
    public function addJsonLdAddsEventUrl(): void
    {
        $day = $this->dayRepository->findByIdentifier(3);

        $jsonLdService = new JsonLdService(new TimeFactory(new DateTimeUtility()));
        $jsonLdService->addJsonLdToPageHeader($day);

        self::assertStringStartsWith(
            'http',
            $jsonLdService->getCollectedJsonLdData()['url'],
        );
    }

    #[Test]
    public function addJsonLdAddsEventFreeEntry(): void
    {
        $day = $this->dayRepository->findByIdentifier(1);

        $jsonLdService = new JsonLdService(new TimeFactory(new DateTimeUtility()));
        $jsonLdService->addJsonLdToPageHeader($day);

        self::assertSame(
            'True',
            $jsonLdService->getCollectedJsonLdData()['isAccessibleForFree'],
        );

        $day = $this->dayRepository->findByIdentifier(3);

        $jsonLdService = new JsonLdService(new TimeFactory(new DateTimeUtility()));
        $jsonLdService->addJsonLdToPageHeader($day);

        self::assertSame(
            'False',
            $jsonLdService->getCollectedJsonLdData()['isAccessibleForFree'],
        );
    }

    #[Test]
    public function addJsonLdAddsEventOffer(): void
    {
        $day = $this->dayRepository->findByIdentifier(3);

        $jsonLdService = new JsonLdService(new TimeFactory(new DateTimeUtility()));
        $jsonLdService->addJsonLdToPageHeader($day);

        $offer = $jsonLdService->getCollectedJsonLdData()['offers'][0];

        self::assertSame(
            'Offer',
            $offer['@type'],
        );
        self::assertSame(
            'TYPO3',
            $offer['name'],
        );
        self::assertSame(
            'https://www.typo3.org',
            $offer['url'],
        );
    }

    #[Test]
    public function addJsonLdAddsEventLocation(): void
    {
        $day = $this->dayRepository->findByIdentifier(3);

        $jsonLdService = new JsonLdService(new TimeFactory(new DateTimeUtility()));
        $jsonLdService->addJsonLdToPageHeader($day);

        $location = $jsonLdService->getCollectedJsonLdData()['location'];

        self::assertSame(
            'Place',
            $location['@type'],
        );
        self::assertSame(
            'jweiland.net',
            $location['name'],
        );
        self::assertSame(
            'PostalAddress',
            $location['address']['@type'],
        );
        self::assertSame(
            'Echterdinger Straße 57',
            $location['address']['streetAddress'],
        );
        self::assertSame(
            '70794',
            $location['address']['postalCode'],
        );
        self::assertSame(
            'Filderstadt',
            $location['address']['addressLocality'],
        );
    }

    #[Test]
    public function addJsonLdAddsEventOrganizer(): void
    {
        $day = $this->dayRepository->findByIdentifier(3);

        $jsonLdService = new JsonLdService(new TimeFactory(new DateTimeUtility()));
        $jsonLdService->addJsonLdToPageHeader($day);

        $organizer = $jsonLdService->getCollectedJsonLdData()['organizer'];

        self::assertSame(
            'Organization',
            $organizer['@type'],
        );
        self::assertSame(
            'Stefan',
            $organizer['name'],
        );
        self::assertSame(
            'https://www.typo3.org',
            $organizer['url'],
        );
    }
}
