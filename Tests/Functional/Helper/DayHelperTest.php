<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Helper;

use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Helper\DayHelper;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Functional test for DayHelper
 */
class DayHelperTest extends FunctionalTestCase
{
    /**
     * @var DayHelper
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

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->subject = $objectManager->get(DayHelper::class);
    }

    public function tearDown()
    {
        unset(
            $this->subject
        );

        parent::tearDown();
    }

    /**
     * @test
     */
    public function getDayFromUriReturnsNull()
    {
        self::assertNull(
            $this->subject->getDayFromUri()
        );
    }

    /**
     * @test
     */
    public function getDayFromUriWithInvalidDayReturnsNull()
    {
        $databaseConnection = $this->getDatabaseConnection();
        $databaseConnection->insertArray(
            'tx_events2_domain_model_day',
            [
                'uid' => 1,
                'pid' => 1
            ]
        );

        $_GET['tx_events2_events']['day'] = '12';
        self::assertNull(
            $this->subject->getDayFromUri()
        );
    }

    /**
     * @test
     */
    public function getDayFromUriWithValidDayReturnsDay()
    {
        $databaseConnection = $this->getDatabaseConnection();
        $databaseConnection->insertArray(
            'tx_events2_domain_model_day',
            [
                'uid' => 1,
                'pid' => 1
            ]
        );

        $_GET['tx_events2_events']['day'] = '1';
        $day = $this->subject->getDayFromUri();

        self::assertInstanceOf(
            Day::class,
            $day
        );
        self::assertSame(
            1,
            $day->getUid()
        );
    }
}
