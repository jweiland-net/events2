<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Controller;

use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Event\PostProcessControllerActionEvent;
use JWeiland\Events2\Event\PostProcessFluidVariablesEvent;
use JWeiland\Events2\Event\PreProcessControllerActionEvent;
use JWeiland\Events2\Service\TypoScriptService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/*
 * A collection of various helper methods to keep
 * our Action Controllers small and clean
 */
class AbstractController extends ActionController
{
    /**
     * @param ConfigurationManagerInterface $configurationManager
     * @throws \Exception
     */
    public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager): void
    {
        $this->configurationManager = $configurationManager;

        $typoScriptSettings = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
            'events2',
            'events2_event' // invalid plugin name, to get fresh unmerged settings
        );

        if (empty($typoScriptSettings['settings'])) {
            throw new \Exception('You have forgotten to add TS-Template of events2', 1580294227);
        }
        $mergedFlexFormSettings = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS,
            'events2'
        ) ?? [];

        // start override
        $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
        $typoScriptService->override(
            $mergedFlexFormSettings,
            $typoScriptSettings['settings']
        );

        $this->settings = $mergedFlexFormSettings;
    }

    public function initializeAction(): void
    {
        // if this value was not set, then it will be filled with 0
        // but that is not good, because UriBuilder accepts 0 as pid, so it's better to set it to NULL
        if (empty($this->settings['pidOfDetailPage'])) {
            $this->settings['pidOfDetailPage'] = null;
        }
        if (empty($this->settings['pidOfSearchPage'])) {
            $this->settings['pidOfSearchPage'] = null;
        }
        if (empty($this->settings['pidOfLocationPage'])) {
            $this->settings['pidOfLocationPage'] = null;
        }
        if (empty($this->settings['pidOfListPage'])) {
            $this->settings['pidOfListPage'] = null;
        }
    }

    protected function initializeView(ViewInterface $view): void
    {
        $this->view->assign('data', $this->configurationManager->getContentObject()->data);
        $this->view->assign('extConf', GeneralUtility::makeInstance(ExtConf::class));
        $this->view->assign('jsVariables', json_encode($this->getJsVariables()));
    }

    /**
     * Create an array with mostly needed variables for JavaScript.
     * That way we don't need JavaScript parts in our templates.
     * I have separated this method to its own method as we have to override these variables
     * in SearchController and I can read them from View after variables are already assigned.
     *
     * @param array $override
     * @return array
     */
    protected function getJsVariables(array $override = []): array
    {
        // Remove pi_flexform from data, as it contains XML/HTML which can be indexed through Solr
        $data = $this->configurationManager->getContentObject()->data;
        unset($data['pi_flexform']);

        $jsVariables = [
            'settings' => $this->settings,
            'data' => $data,
            'localization' => [
                'locationFail' => LocalizationUtility::translate('error.locationFail', 'events2'),
                'remainingText' => LocalizationUtility::translate('remainingLetters', 'events2')
            ]
        ];
        ArrayUtility::mergeRecursiveWithOverrule($jsVariables, $override);

        return $jsVariables;
    }

    protected function postProcessAndAssignFluidVariables(array $variables = []): void
    {
        /** @var PostProcessFluidVariablesEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new PostProcessFluidVariablesEvent(
                $this->request,
                $this->settings,
                $variables
            )
        );

        $this->view->assignMultiple($event->getFluidVariables());
    }

    protected function postProcessControllerAction(?Event $event, ?Day $day): void
    {
        $this->eventDispatcher->dispatch(
            new PostProcessControllerActionEvent(
                $this,
                $event,
                $day,
                $this->settings
            )
        );
    }

    protected function preProcessControllerAction(): void
    {
        $this->eventDispatcher->dispatch(
            new PreProcessControllerActionEvent(
                $this->request,
                $this->arguments,
                $this->settings
            )
        );
    }
}
