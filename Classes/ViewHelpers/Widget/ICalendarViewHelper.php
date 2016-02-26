<?php

namespace JWeiland\Events2\ViewHelpers\Widget;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Stefan Froemken <projects@jweiland.net>, jweiland.net
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
use TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetViewHelper;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ICalendarViewHelper extends AbstractWidgetViewHelper
{
    /**
     * @var \JWeiland\Events2\ViewHelpers\Widget\Controller\ICalendarController
     */
    protected $controller;

    /**
     * @param \JWeiland\Events2\ViewHelpers\Widget\Controller\ICalendarController $controller
     */
    public function injectController(\JWeiland\Events2\ViewHelpers\Widget\Controller\ICalendarController $controller)
    {
        $this->controller = $controller;
    }

    /**
     * call the index action of the controller.
     *
     * @param \JWeiland\Events2\Domain\Model\Event $event
     *
     * @return string
     */
    public function render($event)
    {
        return $this->initiateSubRequest();
    }
}
