<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Traits;

use JWeiland\Events2\Helper\DownloadHelper;

/**
 * Trait to inject DownloadHelper. Mostly used in controllers.
 */
trait InjectDownloadHelperTrait
{
    protected DownloadHelper $downloadHelper;

    public function injectDownloadHelper(DownloadHelper $downloadHelper): void
    {
        $this->downloadHelper = $downloadHelper;
    }
}
