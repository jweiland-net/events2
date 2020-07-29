<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace JWeiland\Events2\Ajax;

use JWeiland\Events2\Ajax\FindDaysForMonth\Ajax;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/** @var Ajax $ajaxObject */
$ajaxObject = GeneralUtility::makeInstance(Ajax::class);
$request = GeneralUtility::_GPmerged('tx_events2_events');
echo $ajaxObject->processAjaxRequest($request['arguments']);
