<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Ajax;

use JWeiland\Events2\Ajax\FindLocations;
use JWeiland\Events2\Domain\Repository\LocationRepository;
use JWeiland\Events2\Tests\Unit\Utility\AccessibleProxies\ExtensionManagementUtilityAccessibleProxy;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Test case.
 */
class FindLocationsTest extends FunctionalTestCase
{
    /**
     * @var FindLocations
     */
    protected $subject;

    /**
     * @var LocationRepository|ObjectProphecy
     */
    protected $locationRepositoryProphecy;

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

        $this->locationRepositoryProphecy = $this->prophesize(LocationRepository::class);

        /** @var ObjectManager|ObjectProphecy $objectManager */
        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager
            ->get(LocationRepository::class)
            ->willReturn($this->locationRepositoryProphecy->reveal());

        $this->subject = new FindLocations($objectManager->reveal());
    }

    public function tearDown(): void
    {
        ExtensionManagementUtilityAccessibleProxy::setCacheManager(null);
        unset($this->subject);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function processAjaxRequestWithNoLocationsReturnsEmptyString(): void
    {
        $queryParams = [
            'tx_events2_events' => [
                'arguments' => [
                    'search' => ''
                ]
            ]
        ];
        $this->locationRepositoryProphecy
            ->findLocations(Argument::cetera())
            ->shouldNotBeCalled();

        $request = new ServerRequest('http://www.example.com/');
        $request = $request->withQueryParams($queryParams);
        self::assertSame(
            '',
            (string)$this->subject->processRequest($request)->getBody()
        );
    }

    /**
     * @test
     */
    public function processAjaxRequestWithHtmlCallsFindLocationsWithoutHtml(): void
    {
        $queryParams = [
            'tx_events2_events' => [
                'arguments' => [
                    'search' => 'Hello german umlauts: öäü. <b>How are you?</b>'
                ]
            ]
        ];
        $this->locationRepositoryProphecy
            ->findLocations('Hello german umlauts: öäü. How are you?')
            ->shouldBeCalled()
            ->willReturn([]);

        $request = new ServerRequest('http://www.example.com/');
        $request = $request->withQueryParams($queryParams);
        $response = $this->subject->processRequest($request);
        $response->getBody()->rewind();
        self::assertSame(
            '',
            (string)$response->getBody()
        );
    }

    /**
     * @test
     */
    public function processAjaxRequestWithTooSmallLocationsReturnsEmptyString(): void
    {
        $queryParams = [
            'tx_events2_events' => [
                'arguments' => [
                    'search' => 'x'
                ]
            ]
        ];
        $this->locationRepositoryProphecy
            ->findLocations('Hello german umlauts: öäü. How are you?')
            ->shouldNotBeCalled();

        $request = new ServerRequest('http://www.example.com/');
        $request = $request->withQueryParams($queryParams);
        self::assertSame(
            '',
            (string)$this->subject->processRequest($request)->getBody()
        );
    }

    /**
     * @test
     */
    public function processAjaxRequestWithLocationsReturnsJson(): void
    {
        $this->locationRepositoryProphecy
            ->findLocations('at h')
            ->shouldBeCalled()
            ->willReturn([
                [
                    'uid' => 123,
                    'label' => 'at home',
                ],
            ]);
        $this->locationRepositoryProphecy
            ->findLocations('mar')
            ->shouldBeCalled()
            ->willReturn([
                [
                    'uid' => 234,
                    'label' => 'Marienheide',
                ],
                [
                    'uid' => 345,
                    'label' => 'Marienhagen',
                ],
            ]);

        $request = new ServerRequest('http://www.example.com/');
        $queryParams = [
            'tx_events2_events' => [
                'arguments' => [
                    'search' => 'at h'
                ]
            ]
        ];
        $request = $request->withQueryParams($queryParams);
        self::assertSame(
            '[{"uid":123,"label":"at home"}]',
            (string)$this->subject->processRequest($request)->getBody()
        );

        $queryParams = [
            'tx_events2_events' => [
                'arguments' => [
                    'search' => 'mar'
                ]
            ]
        ];
        $request = $request->withQueryParams($queryParams);
        self::assertSame(
            '[{"uid":234,"label":"Marienheide"},{"uid":345,"label":"Marienhagen"}]',
            (string)$this->subject->processRequest($request)->getBody()
        );
    }
}
