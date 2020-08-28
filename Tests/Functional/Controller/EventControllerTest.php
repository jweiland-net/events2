<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Controller;

use JWeiland\Events2\Controller\EventController;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Organizer;
use JWeiland\Events2\Domain\Model\Search;
use JWeiland\Events2\Domain\Repository\CategoryRepository;
use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Service\DayRelationService;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\Response;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;

/**
 * Test case.
 */
class EventControllerTest extends FunctionalTestCase
{
    /**
     * @var EventController
     */
    protected $subject;

    /**
     * @var DayRepository|ObjectProphecy
     */
    protected $dayRepositoryProphecy;

    /**
     * @var EventRepository|ObjectProphecy
     */
    protected $eventRepositoryProphecy;

    /**
     * @var CategoryRepository|ObjectProphecy
     */
    protected $categoryRepositoryProphecy;

    /**
     * @var QueryResult|ObjectProphecy
     */
    protected $queryResultProphecy;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/events2'
    ];

    public function setUp()
    {
        parent::setUp();
        $this->setUpBackendUserFromFixture(1);
        $this->importDataSet('ntf://Database/pages.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/tx_events2_domain_model_location.xml');
        $this->setUpFrontendRootPage(1, [__DIR__ . '/../Fixtures/TypoScript/plugin.typoscript']);

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $persistenceManager = $objectManager->get(PersistenceManager::class);
        $dayRelationService = $objectManager->get(DayRelationService::class);
        $querySettings = $objectManager->get(QuerySettingsInterface::class);
        $querySettings->setStoragePageIds([1]);
        $eventRepository = $objectManager->get(EventRepository::class);
        $eventRepository->setDefaultQuerySettings($querySettings);

        $event = new Event();
        $event->setPid(1);
        $event->setEventType('single');
        $event->setEventBegin(new \DateTime('midnight'));
        $event->setTitle('Today');
        $persistenceManager->add($event);
        $persistenceManager->persistAll();

        $organizer = new Organizer();
        $organizer->setPid(1);
        $organizer->setOrganizer('Me');
        $persistenceManager->add($organizer);

        $event = new Event();
        $event->setPid(1);
        $event->setEventType('single');
        $event->setEventBegin(new \DateTime('tomorrow midnight'));
        $event->setTitle('Tomorrow');
        $event->setOrganizer($organizer);
        $persistenceManager->add($event);
        $persistenceManager->persistAll();

        $events = $eventRepository->findAll();
        foreach ($events as $event) {
            $dayRelationService->createDayRelations($event->getUid());
        }

        $this->request = new Request();
        if (method_exists($this->request, 'setControllerAliasToClassNameMapping')) {
            $this->request->setControllerAliasToClassNameMapping([
                'Event' => EventController::class
            ]);
        }
        $this->request->setControllerExtensionName('Events2');
        $this->request->setPluginName('Events');
        $this->request->setControllerName('Event');

        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->subject = $objectManager->get(EventController::class);
    }

    public function tearDown()
    {
        unset(
            $this->subject,
            $this->request
        );

        parent::tearDown();
    }

    /**
     * @test
     */
    public function processRequestWithListSearchResultsWillSearchForEvents()
    {
        $this->request->setControllerActionName('listSearchResults');
        $this->request->setArgument('search', new Search());

        $response = new Response();

        $this->subject->processRequest($this->request, $response);
        $content = $response->getContent();

        self::assertStringContainsString(
            'Event Title 1: Today',
            $content
        );
        self::assertStringContainsString(
            'Event Title 2: Tomorrow',
            $content
        );
    }

    /**
     * @test
     */
    public function processRequestWithListSearchResultsWillSearchEventsBySearch()
    {
        $search = new Search();
        $search->setSearch('today');

        $this->request->setControllerActionName('listSearchResults');
        $this->request->setArgument('search', $search);

        $response = new Response();

        $this->subject->processRequest($this->request, $response);
        $content = $response->getContent();

        self::assertStringContainsString(
            'Event Title 1: Today',
            $content
        );
    }

    /**
     * @test
     */
    public function processRequestWithNewActionWillCollectSelectableCategories()
    {
        $this->request->setControllerActionName('new');

        $response = new Response();

        $this->subject->processRequest($this->request, $response);
        $content = $response->getContent();

        self::assertStringContainsString(
            'Event Title: <>',
            $content
        );
        self::assertStringContainsString(
            'Location Title 1: <Kino>',
            $content
        );
        self::assertStringNotContainsString(
            'Location Title 2',
            $content
        );
    }
}
