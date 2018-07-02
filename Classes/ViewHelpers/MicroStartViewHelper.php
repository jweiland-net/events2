<?php

namespace JWeiland\Events2\ViewHelpers;

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
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class MicroStartViewHelper extends AbstractViewHelper
{
    /**
     * implements a vievHelper to get values from current logged in fe_user.
     *
     * @param string $timer
     *
     * @return void
     */
    public function render($timer = 'debugStart')
    {
        $GLOBALS['TSFE']->register['events2'][$timer] = microtime(true);
    }
}
