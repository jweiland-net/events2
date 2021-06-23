<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Ajax;

use JWeiland\Events2\Ajax\FindDaysForMonth;
use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Service\DatabaseService;
use JWeiland\Events2\Session\UserSession;
use JWeiland\Events2\Tests\Unit\Utility\AccessibleProxies\ExtensionManagementUtilityAccessibleProxy;
use JWeiland\Events2\Utility\DateTimeUtility;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;

/**
 * Test case.
 */
class FindDaysForMonthTest extends FunctionalTestCase
{
    /**
     * @var FindDaysForMonth
     */
    protected $subject;

    /**
     * @var ExtConf
     */
    protected $extConf;

    /**
     * @var UserSession|ObjectProphecy
     */
    protected $userSessionProphecy;

    /**
     * @var DatabaseService|ObjectProphecy
     */
    protected $databaseServiceProphecy;

    /**
     * @var PhpFrontend|ObjectProphecy
     */
    protected $phpFrontendProphecy;

    /**
     * @var CacheManager|ObjectProphecy
     */
    protected $cacheManagerProphecy;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/events2'
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->extConf = new ExtConf(new ExtensionConfiguration());
        $this->extConf->setRecurringPast(3);
        $this->extConf->setRecurringFuture(6);

        $this->userSessionProphecy = $this->prophesize(UserSession::class);
        $this->databaseServiceProphecy = $this->prophesize(DatabaseService::class);

        $this->subject = new FindDaysForMonth(
            $this->extConf,
            new DateTimeUtility(),
            new CacheHashCalculator(),
            $this->userSessionProphecy->reveal(),
            $this->databaseServiceProphecy->reveal(),
            GeneralUtility::makeInstance(EventDispatcher::class)
        );
    }

    public function tearDown(): void
    {
        ExtensionManagementUtilityAccessibleProxy::setCacheManager(null);
        unset($this->subject);
        parent::tearDown();
    }

    /**
     * DataProvider for arguments
     *
     * @return array
     */
    public function dataProviderForProcessAjaxRequestForcesTooHighMonthAndYearInRange(): array
    {
        $arguments = [];
        $arguments['negative values'] = [
            [
                'tx_events2_events' => [
                    'arguments' => [
                        'month' => '-50',
                        'year' => '-2400',
                    ]
                ]
            ],
            [
                'month' => '01',
                'year' => '1500'
            ],
            1
        ];
        $arguments['valid int values'] = [
            [
                'tx_events2_events' => [
                    'arguments' => [
                        'month' => '10',
                        'year' => '2100',
                    ]
                ]
            ],
            [
                'month' => '10',
                'year' => '2100'
            ],
            10
        ];
        $arguments['too huge values'] = [
            [
                'tx_events2_events' => [
                    'arguments' => [
                        'month' => '250',
                        'year' => '12050',
                    ]
                ]
            ],
            [
                'month' => '12',
                'year' => '2500'
            ],
            12
        ];
        $arguments['add 0 to small numbers'] = [
            [
                'tx_events2_events' => [
                    'arguments' => [
                        'month' => '3',
                        'year' => '2145',
                    ]
                ]
            ],
            [
                'month' => '03',
                'year' => '2145'
            ],
            3
        ];

        return $arguments;
    }

    /**
     * @test
     * @dataProvider dataProviderForProcessAjaxRequestForcesTooHighMonthAndYearInRange
     */
    public function processAjaxRequestForcesTooHighMonthAndYearInRange(
        $queryParams,
        $expectedArguments,
        $expectedMonth
    ): void {
        $this->userSessionProphecy
            ->setMonthAndYear(
                $expectedArguments['month'],
                $expectedArguments['year']
            )
            ->shouldBeCalled();

        $request = new ServerRequest('http://www.example.com/');
        $request = $request->withQueryParams($queryParams);
        $this->subject->processRequest($request);
    }

    /**
     * @test
     */
    public function processAjaxRequestWillFindDaysInRangeOfFirstAndLastDateOfMonth(): void
    {
        $currentDate = new \DateTime('now midnight');
        $queryParams = [
            'tx_events2_events' => [
                'arguments' => [
                    'pidOfListPage' => 4321,
                    'month' => (int)$currentDate->format('n'),
                    'year' => (int)$currentDate->format('Y'),
                ]
            ]
        ];
        $startDate = clone $currentDate;
        $startDate->modify('first day of this month');
        $endDate = clone $currentDate;
        $endDate->modify('last day of this month')->modify('tomorrow');

        $this->databaseServiceProphecy
            ->getDaysInRange($startDate, $endDate, [], [])
            ->shouldBeCalled()
            ->willReturn([]);

        $request = new ServerRequest('http://www.example.com/');
        $request = $request->withQueryParams($queryParams);
        $this->subject->processRequest($request);
    }

    /**
     * @test
     */
    public function processAjaxRequestWillFindDaysInRangeOfFirstAndLastDateOfMonthWithStoragePids(): void
    {
        $currentDate = new \DateTime('now midnight');
        $queryParams = [
            'tx_events2_events' => [
                'arguments' => [
                    'storagePids' => '21,22,23',
                    'pidOfListPage' => 4321,
                    'month' => (int)$currentDate->format('n'),
                    'year' => (int)$currentDate->format('Y'),
                ]
            ]
        ];
        $startDate = clone $currentDate;
        $startDate->modify('first day of this month');
        $endDate = clone $currentDate;
        $endDate->modify('last day of this month')->modify('tomorrow');

        $this->databaseServiceProphecy
            ->getDaysInRange($startDate, $endDate, [21, 22, 23], [])
            ->shouldBeCalled()
            ->willReturn([]);

        $request = new ServerRequest('http://www.example.com/');
        $request = $request->withQueryParams($queryParams);
        $this->subject->processRequest($request);
    }

    /**
     * @test
     */
    public function processAjaxRequestWillFindDaysInRangeOfFirstAndLastDateOfMonthWithStoragePidsAndCategories(): void
    {
        $currentDate = new \DateTime('now midnight');
        $queryParams = [
            'tx_events2_events' => [
                'arguments' => [
                    'categories' => '31,32,33',
                    'storagePids' => '21,22,23',
                    'pidOfListPage' => 4321,
                    'month' => (int)$currentDate->format('n'),
                    'year' => (int)$currentDate->format('Y'),
                ]
            ]
        ];
        $startDate = clone $currentDate;
        $startDate->modify('first day of this month');
        $endDate = clone $currentDate;
        $endDate->modify('last day of this month')->modify('tomorrow');

        $this->databaseServiceProphecy
            ->getDaysInRange($startDate, $endDate, [21, 22, 23], [31, 32, 33])
            ->shouldBeCalled()
            ->willReturn([]);

        $request = new ServerRequest('http://www.example.com/');
        $request = $request->withQueryParams($queryParams);
        $this->subject->processRequest($request);
    }

    /**
     * @test
     */
    public function processAjaxRequestWillFindDaysInRangeOfEarliestDateAndLastDateOfMonth(): void
    {
        $currentDate = new \DateTime('-3 months midnight');
        $queryParams = [
            'tx_events2_events' => [
                'arguments' => [
                    'pidOfListPage' => 4321,
                    'month' => (int)$currentDate->format('n'),
                    'year' => (int)$currentDate->format('Y'),
                ]
            ]
        ];
        $startDate = clone $currentDate;
        $endDate = clone $currentDate;
        $endDate->modify('last day of this month')->modify('tomorrow');

        $this->databaseServiceProphecy
            ->getDaysInRange($startDate, $endDate, [], [])
            ->shouldBeCalled()
            ->willReturn([]);

        $request = new ServerRequest('http://www.example.com/');
        $request = $request->withQueryParams($queryParams);
        $this->subject->processRequest($request);
    }

    /**
     * @test
     */
    public function processAjaxRequestWillFindDaysInRangeOfFirstDateAndLatestDateOfMonth(): void
    {
        $currentDate = new \DateTime('+6 months midnight');
        $queryParams = [
            'tx_events2_events' => [
                'arguments' => [
                    'pidOfListPage' => 4321,
                    'month' => (int)$currentDate->format('n'),
                    'year' => (int)$currentDate->format('Y'),
                ]
            ]
        ];
        $startDate = clone $currentDate;
        $startDate->modify('first day of this month');
        $endDate = clone $currentDate;
        $endDate->modify('tomorrow');

        $this->databaseServiceProphecy
            ->getDaysInRange($startDate, $endDate, [], [])
            ->shouldBeCalled()
            ->willReturn([]);

        $request = new ServerRequest('http://www.example.com/');
        $request = $request->withQueryParams($queryParams);
        $this->subject->processRequest($request);
    }
}
