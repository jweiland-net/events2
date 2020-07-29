<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace JWeiland\Events2\Controller;

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Controller for Ajax Requests. Currently only for FindSubCategories
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
    public function callAjaxObjectAction($objectName, $arguments = [])
    {
        if (is_string($objectName)) {
            $className = 'JWeiland\\Events2\\Ajax\\' . ucfirst($objectName);
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
