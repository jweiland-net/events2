<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Traits;

use JWeiland\Events2\Service\Record\LocationRecordService;

/**
 * Trait to inject LocationRecordService.
 */
trait InjectLocationRecordServiceTrait
{
    protected LocationRecordService $locationRecordService;

    public function injectLocationRecordService(LocationRecordService $locationRecordService): void
    {
        $this->locationRecordService = $locationRecordService;
    }
}
