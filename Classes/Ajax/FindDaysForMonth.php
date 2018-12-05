<?php

namespace JWeiland\Events2\Ajax;

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
use JWeiland\Events2\Ajax\FindDaysForMonth\Ajax;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/** @var Ajax $ajaxObject */
$ajaxObject = GeneralUtility::makeInstance(Ajax::class);
$request = GeneralUtility::_GPmerged('tx_events2_events');
echo $ajaxObject->processAjaxRequest($request['arguments']);
