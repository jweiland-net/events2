<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Controller;

use JWeiland\Events2\Controller\DayController;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Filter;
use JWeiland\Events2\Domain\Model\Organizer;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Service\DayRelationService;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\Response;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;

/**
 * Test case.
 */
class DayControllerTest extends FunctionalTestCase
{
    /**
     * @var DayController
     */
    protected $subject;

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
        $this->importDataSet('ntf://Database/pages.xml');
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
                'Day' => DayController::class
            ]);
        }
        $this->request->setControllerExtensionName('Events2');
        $this->request->setPluginName('Events');
        $this->request->setControllerName('Day');

        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->subject = $objectManager->get(DayController::class);
    }

    public function tearDown()
    {
        unset(
            $this->subject,
            $this->request
        );

        parent::tearDown();
    }

    public function listWithEmptyFilterDataProvider()
    {
        return [
            'Action: list' => ['list'],
            'Action: list latest' => ['listLatest'],
            'Action: list today' => ['listToday'],
            'Action: list this week' => ['listThisWeek'],
            'Action: list range' => ['listRange'],
        ];
    }

    /**
     * @test
     *
     * @dataProvider listWithEmptyFilterDataProvider
     */
    public function processRequestWithListActionWillValidateAndAssignFilterToView($action)
    {
        $this->request->setControllerActionName($action);

        $response = new Response();
        $this->subject->processRequest($this->request, $response);
        $content = $response->getContent();

        self::assertStringContainsString(
            'Event Title 1: Today',
            $content
        );
        if ($action !== 'listToday') {
            self::assertStringContainsString(
                'Event Title 2: Tomorrow',
                $content
            );
        }
        self::assertStringNotContainsString(
            'Organizer: 1',
            $content
        );
    }

    public function listWithFilledFilterDataProvider()
    {
        $filter = new Filter();
        $filter->setOrganizer(1);

        return [
            'Action: list' => ['list', $filter],
            'Action: list latest' => ['listLatest', $filter],
            'Action: list today' => ['listToday', $filter],
            'Action: list this week' => ['listThisWeek', $filter],
            'Action: list range' => ['listRange', $filter],
        ];
    }

    /**
     * @test
     *
     * @dataProvider listWithFilledFilterDataProvider
     */
    public function processRequestWithListActionWillAssignFilterToView($action, $filter)
    {
        $this->request->setControllerActionName($action);
        $this->request->setArgument('filter', $filter);

        $response = new Response();

        $this->subject->processRequest($this->request, $response);
        $content = $response->getContent();

        if ($action !== 'listToday') {
            self::assertStringContainsString(
                'Event Title 1: Tomorrow',
                $content
            );
        }
        self::assertStringContainsString(
            'Organizer: 1',
            $content
        );
    }
}
