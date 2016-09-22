<?php

namespace JWeiland\Events2\ViewHelpers\Widget;

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
use TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetViewHelper;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ShowEventDatesViewHelper extends AbstractWidgetViewHelper
{
    /**
     * @var \JWeiland\Events2\ViewHelpers\Widget\Controller\ShowEventDatesController
     */
    protected $controller;

    /**
     * @param \JWeiland\Events2\ViewHelpers\Widget\Controller\ShowEventDatesController $controller
     */
    public function injectController(\JWeiland\Events2\ViewHelpers\Widget\Controller\ShowEventDatesController $controller)
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
    public function render(\JWeiland\Events2\Domain\Model\Event $event)
    {
        return $this->initiateSubRequest();
    }
}
