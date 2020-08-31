<?php

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

    public function setUp()
    {
        parent::setUp();

        $this->locationRepositoryProphecy = $this->prophesize(LocationRepository::class);

        $this->subject = new FindLocations($this->locationRepositoryProphecy->reveal());
    }

    public function tearDown()
    {
        ExtensionManagementUtilityAccessibleProxy::setCacheManager(null);
        unset($this->subject);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function processAjaxRequestWithNoLocationsReturnsEmptyString()
    {
        $queryParams = [
            'tx_events2_events' => [
                'arguments' => [
                    'locationPart' => ''
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
    public function processAjaxRequestWithHtmlCallsFindLocationsWithoutHtml()
    {
        $queryParams = [
            'tx_events2_events' => [
                'arguments' => [
                    'locationPart' => 'Hello german umlauts: öäü. <b>How are you?</b>'
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
    public function processAjaxRequestWithTooSmallLocationsReturnsEmptyString()
    {
        $queryParams = [
            'tx_events2_events' => [
                'arguments' => [
                    'locationPart' => 'x'
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
    public function processAjaxRequestWithLocationsReturnsJson()
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
                    'locationPart' => 'at h'
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
                    'locationPart' => 'mar'
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
