<?php

namespace JWeiland\Events2\Hooks;

/*
 * This file is part of the TYPO3 CMS project.
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
use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Maps2\Domain\Model\Location;
use JWeiland\Maps2\Domain\Model\RadiusResult;
use JWeiland\Maps2\Utility\GeocodeUtility;
use SJBR\StaticInfoTables\Utility\ModelUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
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
        $countries = $this->getDatabaseConnection()->exec_SELECTgetRows(
            'uid, cn_short_en',
            'static_countries',
            'deleted=0',
            '', 'static_countries.cn_short_en', ''
        );
        
        $options = array();
        $options[] = '<option value=""></option>';
        foreach ($countries as $country) {
            $options[] = $this->wrapOption((int)$country['uid'], $country['cn_short_en'], $params['fieldValue'] == $country['uid']);
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
     * Get TYPO3s Database Connection
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
