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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\FlexFormService;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Lang\LanguageService;

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
            $view->assign('classNameForErrors', $this->getErrorClassOnMissConfiguration($flexFormValues));
            $content = $view->render();
        }
        return $content;
    }

    /**
     * Get Label for action Value
     * f.e. Event->listLatest;Event->show;Day->show;Location->show;Video->show -> List Latest
     *
     * @param array $flexFormValues
     * @return string
     */
    protected function getLabelForActionValue(array $flexFormValues)
    {
        $label = 'List';
        $items = ArrayUtility::getValueByPath(
            GeneralUtility::xml2array(
                file_get_contents(ExtensionManagementUtility::extPath('events2') . 'Configuration/FlexForms/Events.xml')
            ),
            'sheets/sDEFAULT/ROOT/el/switchableControllerActions/TCEforms/config/items'
        );
        foreach ($items as $item) {
            if ($flexFormValues['switchableControllerActions'] === $item[1]) {
                $label = $this->getLanguageService()->sL($item[0]);
                break;
            }
        }
        return $label;
    }

    /**
     * Returns an error class name on miss configuration
     *
     * @param array $flexFormSettings
     * @return string
     */
    protected function getErrorClassOnMissConfiguration(array $flexFormSettings)
    {
        $class= '';
        if (
            !empty($flexFormSettings['settings']['preFilterByOrganizer'])
            && !empty($flexFormSettings['settings']['showFilterForOrganizerInFrontend'])
        ) {
            $class = 'message-error';
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
        if (GeneralUtility::compat_version('7.6')) {
            $view->setTemplatePathAndFilename(
                GeneralUtility::getFileAbsFileName('EXT:events2/Resources/Private/Templates/BackendPluginItem.html')
            );
        } else {
            $view->setTemplatePathAndFilename(
                GeneralUtility::getFileAbsFileName('EXT:events2/Resources/Private/Templates/BackendPluginItem62.html')
            );
        }
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
        $settings = [];
        if (!empty($row['pi_flexform'])) {
            /** @var FlexFormService $flexFormService */
            $flexFormService = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Service\\FlexFormService');
            $settings = $flexFormService->convertFlexFormContentToArray($row['pi_flexform']);
            $settings = ArrayUtility::setValueByPath(
                $settings,
                'switchableControllerActions',
                $this->getLabelForActionValue($settings)
            );
        }
        return $settings;
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

    /**
     * Get TYPO3s Language Service
     *
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
