<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Ajax;

use JWeiland\Events2\Ajax\FindSubCategories;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Test case.
 */
class FindSubCategoriesTest extends FunctionalTestCase
{
    protected FindSubCategories $subject;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/events2'
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importDataSet(__DIR__ . '/../Fixtures/sys_category.xml');

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->subject = $objectManager->get(FindSubCategories::class);
    }

    protected function tearDown(): void
    {
        unset($this->subject);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function processAjaxRequestWithEmptyArrayReturnsEmptyJson(): void
    {
        self::assertSame(
            '{}',
            $this->subject->processAjaxRequest([])
        );
    }

    /**
     * @test
     */
    public function processAjaxRequestWillReturnTwoCategories(): void
    {
        self::assertSame(
            '{"2":"Audi","3":"BMW"}',
            $this->subject->processAjaxRequest(
                [
                    'category' => '1'
                ]
            )
        );
    }

    /**
     * @test
     */
    public function processAjaxRequestWillReturnOneCategoryFromRoot(): void
    {
        self::assertSame(
            '{"1":"Cars"}',
            $this->subject->processAjaxRequest(
                [
                    'category' => '0'
                ]
            )
        );
    }
}
