<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Traits;

use JWeiland\Events2\Service\DayRelationService;

/**
 * Trait to inject DayRelationService. Mostly used in controllers.
 */
trait InjectDayRelationServiceTrait
{
    protected DayRelationService $dayRelationService;

    public function injectDayRelationService(DayRelationService $dayRelationService): void
    {
        $this->dayRelationService = $dayRelationService;
    }
}
