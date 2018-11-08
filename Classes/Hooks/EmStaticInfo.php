<?php

namespace JWeiland\Events2\Hooks;

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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Render a selectbox with countries from static_info_tables within ExtensionManager configuration for events2
 */
class EmStaticInfo
{
    /**
     * Render our own custom field for static_info_tables
     *
     * @param array $params
     * @param $configurationForm
     *
     * @return string
     */
    public function renderDefaultCountry(array $params, $configurationForm)
    {
        $options = [];
        $options[] = '<option value=""></option>';

        $countries = $this->getCountries();
        foreach ($countries as $country) {
            $options[] = $this->wrapOption(
                (int)$country['uid'],
                $country['cn_short_en'],
                $params['fieldValue'] == $country['uid']
            );
        }

        return sprintf(
            '<select id="%s" class="%s" name="%s">%s</select>',
            'em-' . $params['propertyName'],
            'form-control',
            $params['fieldName'],
            implode(LF, $options)
        );
    }

    /**
     * Get Countries from static_info_table: static_countries
     *
     * @return array
     */
    protected function getCountries()
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('static_countries');
        $queryBuilder->getRestrictions()->removeAll()->add(
            GeneralUtility::makeInstance(DeletedRestriction::class)
        );
        $countries = $queryBuilder
            ->select('uid', 'cn_short_en')
            ->from('static_countries')
            ->orderBy('cn_short_en', 'ASC')
            ->execute()
            ->fetchAll();

        if (empty($countries)) {
            $countries = [];
        }
        return $countries;
    }

    /**
     * Wrap option tag
     *
     * @param string $value
     * @param string $label
     * @param bool $selected
     *
     * @return string
     */
    protected function wrapOption($value, $label, $selected)
    {
        return sprintf(
            '<option value="%s"%s>%s</option>',
            $value,
            $selected ? ' selected="selected"' : '',
            $label
        );
    }

    /**
     * Get TYPO3s Connection Pool
     *
     * @return ConnectionPool
     */
    protected function getConnectionPool()
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
