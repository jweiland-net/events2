<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Controller;

use JWeiland\Events2\Helper\CalendarHelper;
use JWeiland\Events2\Traits\TypoScriptFrontendControllerTrait;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/*
 * Controller to show the LiteCalendar. Further, it stores the selected month in user-session
 */
class CalendarController extends AbstractController
{
    use TypoScriptFrontendControllerTrait;

    protected CalendarHelper $calendarHelper;

    public function injectCalendarHelper(CalendarHelper $calendarHelper): void
    {
        $this->calendarHelper = $calendarHelper;
    }

    public function showAction(): ResponseInterface
    {
        $frameworkConfiguration = $this->getMergedFrameworkConfiguration();

        $calendarVariables = $this->calendarHelper->getCalendarVariables();
        $calendarVariables['settings'] = $this->settings;
        $calendarVariables['storagePids'] = $frameworkConfiguration['persistence']['storagePid'];
        $calendarVariables['pidOfListPage'] = $this->settings['pidOfListPage'];
        if (!$calendarVariables['pidOfListPage']) {
            $calendarVariables['pidOfListPage'] = $this->getTypoScriptFrontendController($this->request)->id;
        }

        $this->postProcessAndAssignFluidVariables([
            'environment' => $calendarVariables
        ]);

        return $this->htmlResponse();
    }

    /**
     * Returns the merged (TypoScript + FlexForm) plugin configuration
     */
    protected function getMergedFrameworkConfiguration(): array
    {
        return $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
        );
    }
}
