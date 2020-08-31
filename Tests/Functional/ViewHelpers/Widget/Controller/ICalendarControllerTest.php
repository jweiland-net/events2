<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\ViewHelpers\Widget\Controller;

use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Location;
use JWeiland\Events2\Domain\Model\Organizer;
use JWeiland\Events2\Domain\Model\Time;
use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Service\DayRelationService;
use JWeiland\Events2\ViewHelpers\Widget\Controller\ICalendarController;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Response;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Fluid\Core\Widget\WidgetContext;
use TYPO3\CMS\Fluid\Core\Widget\WidgetRequest;

/**
 * Test case.
 */
class ICalendarControllerTest extends FunctionalTestCase
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
     * @var string
     */
    protected $tempDirectory = '';

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/events2'
    ];

    public function setUp()
    {
        parent::setUp();
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);

        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->dayRepository = $this->objectManager->get(DayRepository::class);
        $this->querySettings = $this->objectManager->get(QuerySettingsInterface::class);
        $this->querySettings->setStoragePageIds([11, 40]);
        $this->dayRepository->setDefaultQuerySettings($this->querySettings);
        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $dayRelationService = $this->objectManager->get(DayRelationService::class);
        $eventRepository = $this->objectManager->get(EventRepository::class);
        $eventRepository->setDefaultQuerySettings($this->querySettings);

        $time = new Time();
        $time->setPid(11);
        $time->setTimeBegin('08:00');
        $time->setTimeEnd('10:00');

        $organizer = new Organizer();
        $organizer->setPid(11);
        $organizer->setOrganizer('Stefan');

        $location = new Location();
        $location->setPid(11);
        $location->setLocation('Market');

        $eventBegin = new \DateTime('midnight');
        $eventBegin->modify('first day of this month')->modify('+4 days')->modify('-2 months');

        $event = new Event();
        $event->setPid(11);
        $event->setEventType('single');
        $event->setTopOfList(false);
        $event->setTitle('Week market');
        $event->setTeaser('');
        $event->setEventBegin($eventBegin);
        $event->setEventTime($time);
        $event->setEachWeeks(0);
        $event->setEachMonths(0);
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

        $this->tempDirectory = Environment::getPublicPath() . '/' . 'typo3temp/tx_events2/iCal/';
    }

    public function tearDown()
    {
        unset($this->subject);
        $files = array_slice(scandir($this->tempDirectory), 2);
        foreach ($files as $file) {
            unlink($this->tempDirectory . $file);
        }
        parent::tearDown();
    }

    /**
     * @test
     */
    public function indexActionWithoutDayWillReturnEmptyString()
    {
        $widgetContext = $this->objectManager->get(WidgetContext::class);
        $request = $this->objectManager->get(WidgetRequest::class);
        $request->setWidgetContext($widgetContext);
        $response = $this->objectManager->get(Response::class);
        $iCalendarController = $this->objectManager->get(ICalendarController::class);
        $iCalendarController->processRequest($request, $response);

        // Yes, content is initially null
        self::assertNull(
            $response->getContent()
        );
    }

    /**
     * @test
     */
    public function indexActionWithDayWillGenerateDownloadLink()
    {
        $day = $this->dayRepository->findByIdentifier(1);

        $widgetContext = $this->objectManager->get(WidgetContext::class);
        $widgetContext->setControllerObjectName(ICalendarController::class);
        $widgetContext->setParentExtensionName('events2');
        $widgetContext->setParentPluginName('event');
        $widgetContext->setParentPluginNamespace('events2_event');
        $widgetContext->setWidgetIdentifier('@widget_0');
        $widgetContext->setWidgetConfiguration([
            'day' => $day
        ]);
        $widgetRequest = $this->objectManager->get(WidgetRequest::class);
        $widgetRequest->setWidgetContext($widgetContext);
        $response = $this->objectManager->get(Response::class);
        $iCalendarController = $this->objectManager->get(ICalendarController::class);
        $iCalendarController->processRequest($widgetRequest, $response);

        self::assertContains(
            'Export date',
            $response->getContent()
        );
        self::assertContains(
            'typo3temp/tx_events2',
            $response->getContent()
        );
    }

    /**
     * @test
     */
    public function indexActionWithDayCreatesICal()
    {
        $day = $this->dayRepository->findByIdentifier(1);

        $widgetContext = $this->objectManager->get(WidgetContext::class);
        $widgetContext->setControllerObjectName(ICalendarController::class);
        $widgetContext->setParentExtensionName('events2');
        $widgetContext->setParentPluginName('event');
        $widgetContext->setParentPluginNamespace('events2_event');
        $widgetContext->setWidgetIdentifier('@widget_0');
        $widgetContext->setWidgetConfiguration([
            'day' => $day
        ]);
        $widgetRequest = $this->objectManager->get(WidgetRequest::class);
        $widgetRequest->setWidgetContext($widgetContext);
        $response = $this->objectManager->get(Response::class);
        $iCalendarController = $this->objectManager->get(ICalendarController::class);
        $iCalendarController->processRequest($widgetRequest, $response);

        $files = array_slice(scandir($this->tempDirectory), 2, 1);
        $content = file_get_contents($this->tempDirectory . $files[0]);
        self::assertContains(
            'BEGIN:VCALENDAR',
            $content
        );
        self::assertContains(
            'VERSION:2.0',
            $content
        );
        self::assertContains(
            'PRODID:',
            $content
        );
        self::assertContains(
            'BEGIN:VEVENT',
            $content
        );
        self::assertContains(
            'END:VEVENT',
            $content
        );
        self::assertContains(
            'END:VCALENDAR',
            $content
        );
    }

    /**
     * @test
     */
    public function indexActionWithDayCreatesICalWithDTSTART()
    {
        /** @var Day $day */
        $day = $this->dayRepository->findByIdentifier(1);

        $widgetContext = $this->objectManager->get(WidgetContext::class);
        $widgetContext->setControllerObjectName(ICalendarController::class);
        $widgetContext->setParentExtensionName('events2');
        $widgetContext->setParentPluginName('event');
        $widgetContext->setParentPluginNamespace('events2_event');
        $widgetContext->setWidgetIdentifier('@widget_0');
        $widgetContext->setWidgetConfiguration([
            'day' => $day
        ]);
        $widgetRequest = $this->objectManager->get(WidgetRequest::class);
        $widgetRequest->setWidgetContext($widgetContext);
        $response = $this->objectManager->get(Response::class);
        $iCalendarController = $this->objectManager->get(ICalendarController::class);
        $iCalendarController->processRequest($widgetRequest, $response);

        $files = array_slice(scandir($this->tempDirectory), 2, 1);
        $content = file_get_contents($this->tempDirectory . $files[0]);

        $dateTimeZone = new \DateTimeZone('UTC');
        $expectedDate = $day->getEvent()->getEventBegin();
        $expectedDate->modify('+8 hours');
        $expectedDate->setTimezone($dateTimeZone);

        self::assertContains(
            'DTSTART:' . $expectedDate->format('Ymd\THis\Z'),
            $content
        );
    }

    /**
     * @test
     */
    public function indexActionWithDayCreatesICalWithDTEND()
    {
        /** @var Day $day */
        $day = $this->dayRepository->findByIdentifier(1);

        $widgetContext = $this->objectManager->get(WidgetContext::class);
        $widgetContext->setControllerObjectName(ICalendarController::class);
        $widgetContext->setParentExtensionName('events2');
        $widgetContext->setParentPluginName('event');
        $widgetContext->setParentPluginNamespace('events2_event');
        $widgetContext->setWidgetIdentifier('@widget_0');
        $widgetContext->setWidgetConfiguration([
            'day' => $day
        ]);
        $widgetRequest = $this->objectManager->get(WidgetRequest::class);
        $widgetRequest->setWidgetContext($widgetContext);
        $response = $this->objectManager->get(Response::class);
        $iCalendarController = $this->objectManager->get(ICalendarController::class);
        $iCalendarController->processRequest($widgetRequest, $response);

        $files = array_slice(scandir($this->tempDirectory), 2, 1);
        $content = file_get_contents($this->tempDirectory . $files[0]);

        $dateTimeZone = new \DateTimeZone('UTC');
        $expectedDate = $day->getEvent()->getEventBegin();
        $expectedDate->modify('+10 hours');
        $expectedDate->setTimezone($dateTimeZone);

        self::assertContains(
            'DTEND:' . $expectedDate->format('Ymd\THis\Z'),
            $content
        );
    }
}
