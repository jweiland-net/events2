<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\PageTitleProvider;

use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\PageTitleProvider\Events2PageTitleProvider;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for Events2PageTitleProvider
 */
class Events2PageTitleProviderTest extends FunctionalTestCase
{
    protected Events2PageTitleProvider $subject;

    protected array $testExtensionsToLoad = [
        'jweiland/events2',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/PageTitleProvider.csv');

        $this->subject = new Events2PageTitleProvider(
            GeneralUtility::makeInstance(EventRepository::class),
            GeneralUtility::makeInstance(DayRepository::class),
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->pageTitleProvider,
        );

        parent::tearDown();
    }

    /**
     * @test
     */
    public function findDayWithDateTimeOfTodayWillFindExactlyMatchingDay(): void
    {
        $request = new ServerRequest('https://www.example.com', 'GET');
        $request = $request->withQueryParams([
            'tx_events2_show' => [
                'controller' => 'Event',
                'action' => 'show',
                'event' => '1',
                'timestamp' => '1715299200',
            ],
        ]);
        $GLOBALS['TYPO3_REQUEST'] = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);

        self::assertSame(
            'Nice title for detail page - 10.05.2024',
            $this->subject->getTitle(),
        );
    }
}
