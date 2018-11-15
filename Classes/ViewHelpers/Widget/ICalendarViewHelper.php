<?php

namespace JWeiland\Events2\ViewHelpers\Widget;

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

use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\ViewHelpers\Widget\Controller\ICalendarController;
use TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetViewHelper;

/**
 * A Fluid widget to create a link for downloading an iCal file
 */
class ICalendarViewHelper extends AbstractWidgetViewHelper
{
    /**
     * @var ICalendarController
     */
    protected $controller;

    /**
     * @param ICalendarController $controller
     */
    public function injectController(ICalendarController $controller)
    {
        $this->controller = $controller;
    }

    /**
     * Initialize arguments.
     *
     * @api
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('day', Day::class, 'The day object to create the download iCal file for', true);
    }

    /**
     * Renders a link to download an iCal file
     */
    public function render(): string
    {
        return $this->initiateSubRequest();
    }
}
