<?php
declare(strict_types = 1);
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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Render a little table with information from FlexForm into
 */
class RenderPluginItem
{
    /**
     * Change rootUid to a value defined in EXT_CONF.
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
    protected function getLabelForActionValue(array $flexFormValues): string
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
     * Get Fluid Standalone View
     *
     * @return StandaloneView
     */
    protected function getView(): StandaloneView
    {
        /** @var StandaloneView $view */
        $view = GeneralUtility::makeInstance(StandaloneView::class);
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
    protected function getTitleOfOrganizer(array $flexFormSettings): string
    {
        $title = '';
        if (empty($flexFormSettings['settings']['preFilterByOrganizer'])) {
            return $title;
        }

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_organizer');
        $queryBuilder->getRestrictions()->removeAll()->add(
            GeneralUtility::makeInstance(DeletedRestriction::class)
        );
        $row = $queryBuilder
            ->select('*')
            ->from('tx_events2_domain_model_organizer')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($flexFormSettings['settings']['preFilterByOrganizer'], \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();

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
    protected function getFlexFormSettings(array $row): array
    {
        $settings = [];
        if (!empty($row['pi_flexform'])) {
            if (version_compare(TYPO3_branch, '9.5', '<')) {
                /** @var \TYPO3\CMS\Extbase\Service\FlexFormService $flexFormService */
                $flexFormService = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Service\FlexFormService::class);
            } else {
                /** @var \TYPO3\CMS\Core\Service\FlexFormService $flexFormService */
                $flexFormService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Service\FlexFormService::class);
            }
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
     * Get TYPO3s Connection Pool
     *
     * @return ConnectionPool
     */
    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }

    /**
     * Get TYPO3s Language Service
     *
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
