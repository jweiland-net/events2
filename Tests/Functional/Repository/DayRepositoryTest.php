<?php

namespace JWeiland\Events2\Tests\Unit\Functional\Repository;

/*
 * This file is part of the events2 project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Domain\Repository\EventRepository;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Functional test for DayRepository
 */
class DayRepositoryTest extends FunctionalTestCase
{
    /**
     * @var DayRepository
     */
    protected $dayRepository;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/events2'];

    public function setUp()
    {
        parent::setUp();
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->dayRepository = $objectManager->get(EventRepository::class);

        //$this->importDataSet(__DIR__ . '/../Fixtures/tx_events2_domain_model_event.xml');
    }

    public function tearDown()
    {
        unset($this->dayRepository);
        parent::tearDown();
    }

    /**
     * Test if storage page UIDs are working
     *
     * @test
     */
    public function findRecordsByPid()
    {
        $events = $this->dayRepository->findByPid(11);

        $this->assertSame(
            $events->count(),
            11
        );
    }
}
