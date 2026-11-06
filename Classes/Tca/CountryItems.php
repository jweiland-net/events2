<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tca;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Country\CountryProvider;

/**
 * itemsProcFunc to fill a select field with all countries of the TYPO3 core.
 * The value is the ISO 3166-1 alpha-2 code. The label will be translated by FormEngine.
 */
#[Autoconfigure(public: true)]
readonly class CountryItems
{
    public function __construct(
        private CountryProvider $countryProvider,
    ) {}

    /**
     * @param array<string, mixed> $params
     */
    public function addCountries(array &$params): void
    {
        foreach ($this->countryProvider->getAll() as $country) {
            $params['items'][] = [
                'label' => $country->getLocalizedNameLabel(),
                'value' => $country->getAlpha2IsoCode(),
            ];
        }
    }
}
