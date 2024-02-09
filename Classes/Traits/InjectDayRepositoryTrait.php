<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Traits;

use JWeiland\Events2\Domain\Repository\DayRepository;

/**
 * Trait to inject DayRepository. Mostly used in controllers.
 */
trait InjectDayRepositoryTrait
{
    protected DayRepository $dayRepository;

    public function injectDayRepository(DayRepository $dayRepository): void
    {
        $this->dayRepository = $dayRepository;
    }
}
