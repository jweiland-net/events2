<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Controller;

use JWeiland\Events2\Ajax\AjaxInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/*
 * Controller for Ajax Requests. Currently only for FindSubCategories
 */
class AjaxController extends ActionController
{
    /**
     * This ajax action can only be called by Ajax scripts based on pageType.
     * eID scripts has its own bootstrap.
     */
    public function callAjaxObjectAction(string $objectName, array $arguments = []): string
    {
        if ($objectName !== '') {
            $className = 'JWeiland\\Events2\\Ajax\\' . ucfirst($objectName);
            if (class_exists($className)) {
                $object = $this->objectManager->get($className);
                if ($object instanceof AjaxInterface) {
                    return $object->processAjaxRequest($arguments);
                }
            }
        }

        // @ToDo: Empty String will be returned as NULL in callActionMethod of Extbase
        return '';
    }
}
