<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Traits;

use JWeiland\Events2\Helper\ICalendarHelper;

/**
 * Trait to inject ICalendarHelper. Mostly used in controllers.
 */
trait InjectICalendarHelperTrait
{
    protected ICalendarHelper $iCalendarHelper;

    public function injectICalendarHelper(ICalendarHelper $iCalendarHelper): void
    {
        $this->iCalendarHelper = $iCalendarHelper;
    }
}
