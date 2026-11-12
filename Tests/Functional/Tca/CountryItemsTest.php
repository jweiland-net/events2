<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Tca;

use JWeiland\Events2\Tca\CountryItems;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case for class \JWeiland\Events2\Tca\CountryItems
 */
class CountryItemsTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'jweiland/events2',
    ];

    #[Test]
    public function addCountriesAddsCountriesWithIsoCodeAsValue(): void
    {
        $params = [
            'items' => [
                ['label' => '', 'value' => ''],
            ],
        ];

        // Instantiate like FormEngine does for itemsProcFunc
        GeneralUtility::makeInstance(CountryItems::class)->addCountries($params);

        $itemsByValue = array_column($params['items'], 'label', 'value');
        self::assertSame('', $itemsByValue['']);
        self::assertSame('LLL:EXT:core/Resources/Private/Language/Iso/countries.xlf:DE.name', $itemsByValue['DE']);
    }
}
