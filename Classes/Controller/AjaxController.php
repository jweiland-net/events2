<?php

namespace JWeiland\Events2\Controller;

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
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class AjaxController extends ActionController
{
    /**
     * this ajax action can only call Ajax scripts based on pageType
     * eID scripts has its own bootstrap.
     *
     * @param string $objectName Which Ajax Object has to be called
     * @param array  $arguments  Arguments which have to be send to the Ajax Object
     *
     * @return string
     */
    public function callAjaxObjectAction($objectName, $arguments = array())
    {
        if (is_string($objectName)) {
            $className = 'JWeiland\\Events2\\Ajax\\'.ucfirst($objectName);
            if (class_exists($className)) {
                $object = $this->objectManager->get($className);
                if (method_exists($object, 'processAjaxRequest')) {
                    $result = $object->processAjaxRequest($arguments);

                    return $result;
                }
            }
        }

        return '';
    }
}
