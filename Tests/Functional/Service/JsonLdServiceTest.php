<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Service;

use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Link;
use JWeiland\Events2\Domain\Model\Location;
use JWeiland\Events2\Domain\Model\Organizer;
use JWeiland\Events2\Domain\Model\Time;
use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Service\DayRelationService;
use JWeiland\Events2\Service\JsonLdService;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;

/**
 * Functional test for JsonLdService
 */
class JsonLdServiceTest extends FunctionalTestCase
{
    /**
     * @var DayRepository
     */
    protected $dayRepository;

    /**
     * @var QuerySettingsInterface
     */
    protected $querySettings;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/events2'
    ];

    public function setUp()
    {
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['REQUEST_URI'] = 'index.php';
        parent::setUp();
        $this->importDataSet('ntf://Database/pages.xml');
        parent::setUpFrontendRootPage(1);

        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->dayRepository = $this->objectManager->get(DayRepository::class);
        $this->querySettings = $this->objectManager->get(QuerySettingsInterface::class);
        $this->querySettings->setStoragePageIds([11, 40]);
        $this->dayRepository->setDefaultQuerySettings($this->querySettings);
        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $dayRelationService = $this->objectManager->get(DayRelationService::class);
        $eventRepository = $this->objectManager->get(EventRepository::class);
        $eventRepository->setDefaultQuerySettings($this->querySettings);

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

        $eventBegin = new \DateTime('midnight');
        $eventBegin->modify('first day of this month')->modify('+4 days')->modify('-2 months');
        $eventEnd = clone $eventBegin;
        $eventEnd->modify('+1 day');

        $event = new Event();
        $event->setPid(11);
        $event->setEventType('duration');
        $event->setTopOfList(false);
        $event->setTitle('Week market');
        $event->setTeaser('');
        $event->setEventBegin($eventBegin);
        $event->setEventEnd($eventEnd);
        $event->setFreeEntry(true);
        $event->setOrganizer($organizer);
        $event->setLocation($location);
        $persistenceManager->add($event);

        $time = new Time();
        $time->setTimeBegin('08:00');
        $time->setTimeEntry('07:00');
        $time->setDuration('02:00');
        $time->setTimeEnd('10:00');

        $event = new Event();
        $event->setPid(11);
        $event->setEventType('single');
        $event->setTopOfList(false);
        $event->setTitle('Birthday');
        $event->setDetailInformations('Happy birthday to you, happy birthday to you, ...');
        $event->setEventBegin($eventBegin);
        $event->setEventTime($time);
        $event->setTicketLink($link);
        $event->setFreeEntry(false);
        $event->setOrganizer($organizer);
        $event->setLocation($location);
        $persistenceManager->add($event);

        $persistenceManager->persistAll();

        $extConf = GeneralUtility::makeInstance(ExtConf::class);
        $extConf->setRecurringPast(3);
        $extConf->setRecurringFuture(6);
        $events = $eventRepository->findAll();
        foreach ($events as $event) {
            $dayRelationService->createDayRelations($event->getUid());
        }
    }

    public function tearDown()
    {
        unset($this->dayRepository);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function addJsonLdAddsEventBeginDate()
    {
        /** @var Day $day */
        $day = $this->dayRepository->findByIdentifier(1);

        $jsonLdService = GeneralUtility::makeInstance(JsonLdService::class);
        $jsonLdService->addJsonLdToPageHeader($day);

        self::assertSame(
            $day->getEvent()->getEventBegin()->format('Y-m-d'),
            $jsonLdService->getCollectedJsonLdData()['startDate']
        );
    }

    /**
     * @test
     */
    public function addJsonLdAddsEventEndDate()
    {
        /** @var Day $day */
        $day = $this->dayRepository->findByIdentifier(1);

        $jsonLdService = GeneralUtility::makeInstance(JsonLdService::class);
        $jsonLdService->addJsonLdToPageHeader($day);

        self::assertSame(
            $day->getEvent()->getEventEnd()->format('Y-m-d'),
            $jsonLdService->getCollectedJsonLdData()['endDate']
        );
    }

    /**
     * @test
     */
    public function addJsonLdAddsTimeStartDate()
    {
        /** @var Day $day */
        $day = $this->dayRepository->findByIdentifier(3);

        $jsonLdService = GeneralUtility::makeInstance(JsonLdService::class);
        $jsonLdService->addJsonLdToPageHeader($day);

        self::assertSame(
            $day->getEvent()->getEventTime()->getTimeBeginAsDateTime()->format('Y-m-d\TH:i:s'),
            $jsonLdService->getCollectedJsonLdData()['startDate']
        );
    }

    /**
     * @test
     */
    public function addJsonLdAddsTimeEntryDate()
    {
        /** @var Day $day */
        $day = $this->dayRepository->findByIdentifier(3);

        $jsonLdService = GeneralUtility::makeInstance(JsonLdService::class);
        $jsonLdService->addJsonLdToPageHeader($day);

        self::assertSame(
            $day->getEvent()->getEventTime()->getTimeEntryAsDateTime()->format('Y-m-d\TH:i:s'),
            $jsonLdService->getCollectedJsonLdData()['doorTime']
        );
    }

    /**
     * @test
     */
    public function addJsonLdAddsDuration()
    {
        /** @var Day $day */
        $day = $this->dayRepository->findByIdentifier(3);

        $jsonLdService = GeneralUtility::makeInstance(JsonLdService::class);
        $jsonLdService->addJsonLdToPageHeader($day);

        list($hours, $minutes) = GeneralUtility::trimExplode(
            ':',
            $day->getEvent()->getEventTime()->getDuration()
        );

        self::assertSame(
            'PT' . (int)$hours . 'H' . (int)$minutes . 'M',
            $jsonLdService->getCollectedJsonLdData()['duration']
        );
    }

    /**
     * @test
     */
    public function addJsonLdAddsTimeEndDate()
    {
        /** @var Day $day */
        $day = $this->dayRepository->findByIdentifier(3);

        $jsonLdService = GeneralUtility::makeInstance(JsonLdService::class);
        $jsonLdService->addJsonLdToPageHeader($day);

        self::assertSame(
            $day->getEvent()->getEventTime()->getTimeEndAsDateTime()->format('Y-m-d\TH:i:s'),
            $jsonLdService->getCollectedJsonLdData()['endDate']
        );
    }

    /**
     * @test
     */
    public function addJsonLdAddsEventTitle()
    {
        /** @var Day $day */
        $day = $this->dayRepository->findByIdentifier(3);

        $jsonLdService = GeneralUtility::makeInstance(JsonLdService::class);
        $jsonLdService->addJsonLdToPageHeader($day);

        self::assertSame(
            $day->getEvent()->getTitle(),
            $jsonLdService->getCollectedJsonLdData()['name']
        );
    }

    /**
     * @test
     */
    public function addJsonLdAddsEventDescription()
    {
        /** @var Day $day */
        $day = $this->dayRepository->findByIdentifier(3);

        $jsonLdService = GeneralUtility::makeInstance(JsonLdService::class);
        $jsonLdService->addJsonLdToPageHeader($day);

        self::assertSame(
            $day->getEvent()->getDetailInformations(),
            $jsonLdService->getCollectedJsonLdData()['description']
        );
    }

    /**
     * @test
     */
    public function addJsonLdAddsEventUrl()
    {
        /** @var Day $day */
        $day = $this->dayRepository->findByIdentifier(3);

        $jsonLdService = GeneralUtility::makeInstance(JsonLdService::class);
        $jsonLdService->addJsonLdToPageHeader($day);

        self::assertStringStartsWith(
            'http',
            $jsonLdService->getCollectedJsonLdData()['url']
        );
    }

    /**
     * @test
     */
    public function addJsonLdAddsEventFreeEntry()
    {
        /** @var Day $day */
        $day = $this->dayRepository->findByIdentifier(1);

        $jsonLdService = GeneralUtility::makeInstance(JsonLdService::class);
        $jsonLdService->addJsonLdToPageHeader($day);

        self::assertSame(
            'True',
            $jsonLdService->getCollectedJsonLdData()['isAccessibleForFree']
        );

        /** @var Day $day */
        $day = $this->dayRepository->findByIdentifier(3);

        $jsonLdService = GeneralUtility::makeInstance(JsonLdService::class);
        $jsonLdService->addJsonLdToPageHeader($day);

        self::assertSame(
            'False',
            $jsonLdService->getCollectedJsonLdData()['isAccessibleForFree']
        );
    }

    /**
     * @test
     */
    public function addJsonLdAddsEventOffer()
    {
        /** @var Day $day */
        $day = $this->dayRepository->findByIdentifier(3);

        $jsonLdService = GeneralUtility::makeInstance(JsonLdService::class);
        $jsonLdService->addJsonLdToPageHeader($day);

        $offer = $jsonLdService->getCollectedJsonLdData()['offers'][0];

        self::assertSame(
            'Offer',
            $offer['@type']
        );
        self::assertSame(
            'TYPO3',
            $offer['name']
        );
        self::assertSame(
            'https://www.typo3.org',
            $offer['url']
        );
    }

    /**
     * @test
     */
    public function addJsonLdAddsEventLocation()
    {
        /** @var Day $day */
        $day = $this->dayRepository->findByIdentifier(3);

        $jsonLdService = GeneralUtility::makeInstance(JsonLdService::class);
        $jsonLdService->addJsonLdToPageHeader($day);

        $location = $jsonLdService->getCollectedJsonLdData()['location'];

        self::assertSame(
            'Place',
            $location['@type']
        );
        self::assertSame(
            'jweiland.net',
            $location['name']
        );
        self::assertSame(
            'PostalAddress',
            $location['address']['@type']
        );
        self::assertSame(
            'Echterdinger Straße 57',
            $location['address']['streetAddress']
        );
        self::assertSame(
            '70794',
            $location['address']['postalCode']
        );
        self::assertSame(
            'Filderstadt',
            $location['address']['addressLocality']
        );
    }

    /**
     * @test
     */
    public function addJsonLdAddsEventOrganizer()
    {
        /** @var Day $day */
        $day = $this->dayRepository->findByIdentifier(3);

        $jsonLdService = GeneralUtility::makeInstance(JsonLdService::class);
        $jsonLdService->addJsonLdToPageHeader($day);

        $organizer = $jsonLdService->getCollectedJsonLdData()['organizer'];

        self::assertSame(
            'Organization',
            $organizer['@type']
        );
        self::assertSame(
            'Stefan',
            $organizer['name']
        );
        self::assertSame(
            'https://www.typo3.org',
            $organizer['url']
        );
    }
}
