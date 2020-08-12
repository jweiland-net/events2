<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\PageTitleProvider;

use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\PageTitleProvider\Events2PageTitleProvider;
use JWeiland\Events2\Service\DayRelationService;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

/**
 * Functional test for Events2PageTitleProvider
 */
class Events2PageTitleProviderTest extends FunctionalTestCase
{
    /**
     * @var Events2PageTitleProvider
     */
    protected $subject;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/events2'
    ];

    public function setUp()
    {
        parent::setUp();

        $pageId = 15;
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $persistenceManager = $objectManager->get(PersistenceManagerInterface::class);
        $querySettings = $objectManager->get(QuerySettingsInterface::class);
        $querySettings->setStoragePageIds([$pageId]);
        $eventRepository = $objectManager->get(EventRepository::class);
        $eventRepository->setDefaultQuerySettings($querySettings);
        $dayRelationService = $objectManager->get(DayRelationService::class);
        $this->subject = new Events2PageTitleProvider();

        $event = new Event();
        $event->setPid($pageId);
        $event->setEventType('single');
        $event->setTopOfList(false);
        $event->setTitle('Nice title for detail page');
        $event->setEventBegin(new \DateTime('midnight'));
        $event->setXth(0);
        $event->setWeekday(0);
        $event->setEachWeeks(0);
        $event->setEachMonths(0);
        $event->setRecurringEnd(null);
        $event->setFreeEntry(false);
        $persistenceManager->add($event);
        $persistenceManager->persistAll();

        $events = $eventRepository->findAll();
        foreach ($events as $event) {
            $dayRelationService->createDayRelations($event->getUid());
        }
    }

    public function tearDown()
    {
        unset($this->pageTitleProvider);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function findDayWithDateTimeOfTodayWillFindExactlyMatchingDay()
    {
        $record = $this->getDatabaseConnection()->selectSingleRow(
            '*',
            'tx_events2_domain_model_day',
            'event=1'
        );

        $date = new \DateTime('midnight');
        $_GET['tx_events2_events']['controller'] = 'Event';
        $_GET['tx_events2_events']['action'] = 'show';
        $_GET['tx_events2_events']['event'] = 1;
        $_GET['tx_events2_events']['timestamp'] = $date->format('U');

        self::assertSame(
            'Nice title for detail page - ' . $date->format('d.m.Y'),
            $this->subject->getTitle()
        );
    }
}
