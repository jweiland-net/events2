<?php

namespace JWeiland\Events2\Controller;

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

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class DayController extends AbstractController
{
    /**
     * action showByTimestamp.
     *
     * @param int $timestamp
     *
     * @return void
     */
    public function showByTimestampAction($timestamp)
    {
        $days = $this->dayRepository->findByTimestamp((int)$timestamp);
        $this->view->assign('days', $days);
    }
}
