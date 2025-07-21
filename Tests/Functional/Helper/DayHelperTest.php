<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Helper;

use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Helper\DayHelper;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for DayHelper
 */
class DayHelperTest extends FunctionalTestCase
{
    protected DayHelper $subject;

    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'sjbr/static-info-tables',
        'jweiland/events2',
    ];

    protected function setUp(): void
    {
        self::markTestIncomplete('DayHelperTest not updated until right now');

        parent::setUp();

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->subject = $objectManager->get(DayHelper::class);
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
        );

        parent::tearDown();
    }

    #[Test]
    public function getDayFromUriReturnsNull(): void
    {
        self::assertNull(
            $this->subject->getDayFromUri(),
        );
    }

    #[Test]
    public function getDayFromUriWithInvalidDayReturnsNull(): void
    {
        $databaseConnection = $this->getDatabaseConnection();
        $databaseConnection->insertArray(
            'tx_events2_domain_model_day',
            [
                'uid' => 1,
                'pid' => Events2Constants::PAGE_STORAGE,
            ],
        );

        $_GET['tx_events2_list']['day'] = '12';
        self::assertNull(
            $this->subject->getDayFromUri(),
        );
    }

    #[Test]
    public function getDayFromUriWithValidDayReturnsDay(): void
    {
        $databaseConnection = $this->getDatabaseConnection();
        $databaseConnection->insertArray(
            'tx_events2_domain_model_day',
            [
                'uid' => 1,
                'pid' => Events2Constants::PAGE_STORAGE,
            ],
        );

        $_GET['tx_events2_list']['day'] = '1';
        $day = $this->subject->getDayFromUri();

        self::assertInstanceOf(
            Day::class,
            $day,
        );
        self::assertSame(
            1,
            $day->getUid(),
        );
    }
}
