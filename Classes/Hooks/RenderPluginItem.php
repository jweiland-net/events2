<?php

namespace JWeiland\Events2\Hooks;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Stefan Froemken <projects@jweiland.net>, jweiland.net
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\FlexFormService;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class RenderPluginItem
{
    /**
     * change rootUid to a value defined in EXT_CONF.
     *
     * @param array $parameters
     * @param object $pObj
     * @return string
     */
    public function render($parameters, $pObj)
    {
        $content = '';
        if ($parameters['row']['list_type'] === 'events2_events') {
            $flexFormValues = $this->getFlexFormSettings($parameters['row']);
            $view = $this->getView();
            $view->assign('parameters', $parameters);
            $view->assign('pi_flexform_transformed', $flexFormValues);
            $view->assign('titleOfOrganizer', $this->getTitleOfOrganizer($flexFormValues));
            $view->assign('classNameForWarnings', $this->getWarningClassOnMissConfiguration($flexFormValues));
            $content = $view->render();
        }
        return $content;
    }

    /**
     * Returns a warning class name on miss configuration
     *
     * @param array $flexFormSettings
     * @return string
     */
    protected function getWarningClassOnMissConfiguration(array $flexFormSettings)
    {
        $class= '';
        if (
            !empty($flexFormSettings['settings']['preFilterByOrganizer'])
            && !empty($flexFormSettings['settings']['showFilterForOrganizerInFrontend'])
        ) {
            $class = 'message-warning';
        }
        return $class;
    }

    /**
     * Get Fluid Standalone View
     *
     * @return StandaloneView
     */
    protected function getView()
    {
        /** @var StandaloneView $view */
        $view = GeneralUtility::makeInstance('TYPO3\\CMS\\Fluid\\View\\StandaloneView');
        $view->setTemplatePathAndFilename(
            GeneralUtility::getFileAbsFileName('EXT:events2/Resources/Private/Templates/BackendPluginItem.html')
        );
        return $view;
    }

    /**
     * Get Title of Organizer
     *
     * @param array $flexFormSettings
     * @return string
     */
    protected function getTitleOfOrganizer(array $flexFormSettings)
    {
        $title = '';
        if (empty($flexFormSettings['settings']['preFilterByOrganizer'])) {
            return $title;
        }
        $row = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            '*',
            'tx_events2_domain_model_organizer',
            'uid=' . (int)$flexFormSettings['settings']['preFilterByOrganizer']
        );
        if (empty($row)) {
            return $title;
        }
        return BackendUtility::getRecordTitle(
            'tx_events2_domain_model_organizer',
            $row,
            true
        );
    }

    /**
     * Get Settings from FlexForm
     *
     * @param array $row
     * @return array
     */
    protected function getFlexFormSettings(array $row)
    {
        $settings = array();
        if (!empty($row['pi_flexform'])) {
            /** @var FlexFormService $flexFormService */
            $flexFormService = GeneralUtility::makeInstance(FlexFormService::class);
            $settings = $flexFormService->convertFlexFormContentToArray($row['pi_flexform']);
        }
        return $settings;
    }

    protected function assignOrganizerToView()
    {

    }

    /**
     * Get TYPO3s Database Connection
     *
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
