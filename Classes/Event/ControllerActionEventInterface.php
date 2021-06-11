<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Event;

use TYPO3\CMS\Extbase\Mvc\Request;

interface ControllerActionEventInterface
{
    public function getRequest(): Request;

    /**
     * Get controller name.
     * It's just "Event" or "Day". It's not the full class name.
     *
     * @return string
     */
    public function getControllerName(): string;

    /**
     * Get action name without appended "Action".
     * It's just "list" or "show"
     *
     * @return string
     */
    public function getActionName(): string;

    public function getSettings(): array;
}
