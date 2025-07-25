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
use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Service\JsonLdService;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use JWeiland\Events2\Tests\Functional\Traits\InsertEventTrait;
use JWeiland\Events2\Utility\DateTimeUtility;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for JsonLdService
 */
class JsonLdServiceTest extends FunctionalTestCase
{
    use InsertEventTrait;

    protected DayRepository $dayRepository;

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

        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setConfigArray([]);
        $frontendTypoScript->setSetupArray([
            'config.' => [
                'tx_extbase.' => [
                    'persistence.' => [
                        'storagePid' => Events2Constants::PAGE_STORAGE,
                    ],
                ],
            ],
        ]);

        $request = new ServerRequest('https://www.example.com/', 'GET');
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $request = $request->withAttribute('frontend.typoscript', $frontendTypoScript);

        $GLOBALS['TYPO3_REQUEST'] = $request;

        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setStoragePageIds([Events2Constants::PAGE_STORAGE]);

        $this->dayRepository = GeneralUtility::makeInstance(DayRepository::class);
        $this->dayRepository->setDefaultQuerySettings($querySettings);
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
        $this->createDurationalEvent();

        $day = $this->dayRepository->findByIdentifier(2);

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
        $this->createDurationalEvent();

        $day = $this->dayRepository->findByIdentifier(2);

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
        $this->createSingleEvent();

        $day = $this->dayRepository->findByIdentifier(1);

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
        $this->createSingleEvent();

        $day = $this->dayRepository->findByIdentifier(1);

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
        $this->createSingleEvent();

        $day = $this->dayRepository->findByIdentifier(1);

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
        $this->createSingleEvent();

        $day = $this->dayRepository->findByIdentifier(1);

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
        $this->createSingleEvent();

        $day = $this->dayRepository->findByIdentifier(1);

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
        $this->createSingleEvent();

        $day = $this->dayRepository->findByIdentifier(1);

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
        $this->createSingleEvent();

        $day = $this->dayRepository->findByIdentifier(1);

        $jsonLdService = new JsonLdService(new TimeFactory(new DateTimeUtility()));
        $jsonLdService->addJsonLdToPageHeader($day);

        self::assertStringStartsWith(
            'http',
            $jsonLdService->getCollectedJsonLdData()['url'],
        );
    }

    #[Test]
    public function addJsonLdWithDurationEventWillReturnFreeEntry(): void
    {
        $this->createDurationalEvent();

        $day = $this->dayRepository->findByIdentifier(2);

        $jsonLdService = new JsonLdService(new TimeFactory(new DateTimeUtility()));
        $jsonLdService->addJsonLdToPageHeader($day);

        self::assertSame(
            'True',
            $jsonLdService->getCollectedJsonLdData()['isAccessibleForFree'],
        );
    }

    #[Test]
    public function addJsonLdWithSingleEventWillReturnPaidEntry(): void
    {
        $this->createSingleEvent();

        $day = $this->dayRepository->findByIdentifier(1);

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
        $this->createSingleEvent();

        $day = $this->dayRepository->findByIdentifier(1);

        $jsonLdService = new JsonLdService(new TimeFactory(new DateTimeUtility()));
        $jsonLdService->addJsonLdToPageHeader($day);

        $offer = $jsonLdService->getCollectedJsonLdData()['offers'][0];

        self::assertSame(
            'Offer',
            $offer['@type'],
        );
        self::assertSame(
            'Ticket Link',
            $offer['name'],
        );
        self::assertSame(
            'https://example.com',
            $offer['url'],
        );
    }

    #[Test]
    public function addJsonLdAddsEventLocation(): void
    {
        $this->createSingleEvent();

        $day = $this->dayRepository->findByIdentifier(1);

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
        $this->createSingleEvent();

        $day = $this->dayRepository->findByIdentifier(1);

        $jsonLdService = new JsonLdService(new TimeFactory(new DateTimeUtility()));
        $jsonLdService->addJsonLdToPageHeader($day);

        $organizer = $jsonLdService->getCollectedJsonLdData()['organizer'];

        self::assertSame(
            'Organization',
            $organizer['@type'],
        );
        self::assertSame(
            'Jochen',
            $organizer['name'],
        );
        self::assertSame(
            'https://jweiland.net',
            $organizer['url'],
        );
    }

    protected function createSingleEvent(): void
    {
        $eventBegin = new \DateTimeImmutable('first day of this month midnight');
        $eventBegin = $eventBegin
            ->modify('+4 days')
            ->modify('-2 months');

        $this->insertEvent(
            title: 'Birthday',
            eventBegin: $eventBegin,
            timeBegin: '08:00',
            ticketLink: 'https://example.com',
            additionalFields: [
                'detail_information' => 'Happy birthday to you, happy birthday to you, ...',
            ],
            organizer: 'Jochen',
            organizerLink: 'https://jweiland.net',
            location: 'jweiland.net',
        );

        $this->createDayRelations();
    }

    protected function createDurationalEvent(): void
    {
        $eventBegin = new \DateTimeImmutable('first day of this month midnight');
        $eventBegin = $eventBegin
            ->modify('+4 days')
            ->modify('-2 months');

        $eventEnd = $eventBegin->modify('+1 day');

        $this->insertEvent(
            title: 'Week market',
            eventBegin: $eventBegin,
            additionalFields: [
                'event_type' => 'duration',
                'event_end' => (int)$eventEnd->format('U'),
                'free_entry' => 1,
            ],
            organizer: 'Stefan',
            organizerLink: 'https://www.typo3.org',
            location: 'jweiland.net',
        );

        $this->createDayRelations();
    }
}
