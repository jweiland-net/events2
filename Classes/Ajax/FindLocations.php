<?php

namespace JWeiland\Events2\Ajax;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
$objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');

$request = GeneralUtility::_GPmerged('tx_events2_events');

if (is_array($request) && is_array($request['arguments'])) {
    /** @var \JWeiland\Events2\Ajax\FindLocations\Ajax $ajaxObject */
    $ajaxObject = $objectManager->get('JWeiland\\Events2\\Ajax\\FindLocations\\Ajax');
    echo $ajaxObject->processAjaxRequest($request['arguments']);
} else {
    echo '';
}
