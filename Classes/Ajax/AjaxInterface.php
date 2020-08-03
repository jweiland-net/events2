<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace JWeiland\Events2\Ajax;

/*
 * Interface for all eID requests
 */
interface AjaxInterface
{
    public function processAjaxRequest(array $arguments): string;
}
