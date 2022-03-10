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
use JWeiland\Events2\Helper\CalendarHelper;
use JWeiland\Events2\Service\TypoScriptService;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/*
 * Controller to show the jquery UI calendar. Further is stores the selected month in user-session
 */
class CalendarController extends AbstractController
{
    protected CalendarHelper $calendarHelper;

    public function injectCalendarHelper(CalendarHelper $calendarHelper): void
    {
        $this->calendarHelper = $calendarHelper;
    }

    public function showAction(): void
    {
        $frameworkConfiguration = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
        );

        $calendarVariables = $this->calendarHelper->getCalendarVariables();
        $calendarVariables['settings'] = $this->settings;
        $calendarVariables['pidOfListPage'] = $this->settings['pidOfListPage'] ?: $GLOBALS['TSFE']->id;
        $calendarVariables['storagePids'] = $frameworkConfiguration['persistence']['storagePid'];

        $this->postProcessAndAssignFluidVariables([
            'environment' => $calendarVariables
        ]);
    }
}
