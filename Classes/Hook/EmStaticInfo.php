<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Hook;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Render a selectbox with countries from static_info_tables within ExtensionManager configuration for events2
 */
readonly class EmStaticInfo
{
    /**
     * Render our own custom field for static_info_tables
     */
    public function renderDefaultCountry(array $params, $configurationForm): string
    {
        $options = [];
        $options[] = '<option value=""></option>';

        $countries = $this->getCountries();
        foreach ($countries as $country) {
            $options[] = $this->wrapOption(
                (string)(int)$country['uid'],
                $country['cn_short_en'],
                (int)$params['fieldValue'] === (int)$country['uid'],
            );
        }

        return sprintf(
            '<select id="%s" class="%s" name="%s">%s</select>',
            'em-' . $params['fieldName'],
            'form-control',
            $params['fieldName'],
            implode(LF, $options),
        );
    }

    /**
     * Get Countries from static_info_table: static_countries
     *
     * @return array[]
     */
    protected function getCountries(): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('static_countries');
        $queryBuilder->getRestrictions()->removeAll()->add(
            GeneralUtility::makeInstance(DeletedRestriction::class),
        );

        return $queryBuilder
            ->select('uid', 'cn_short_en')
            ->from('static_countries')
            ->orderBy('cn_short_en', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    protected function wrapOption(string $value, string $label, bool $selected): string
    {
        return sprintf(
            '<option value="%s"%s>%s</option>',
            $value,
            $selected ? ' selected="selected"' : '',
            $label,
        );
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
