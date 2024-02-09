<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Traits;

use JWeiland\Events2\Configuration\ExtConf;

/**
 * Trait to inject ExtConf. Mostly used in controllers.
 */
trait InjectExtConfTrait
{
    protected ExtConf $extConf;

    public function injectExtConf(ExtConf $extConf): void
    {
        $this->extConf = $extConf;
    }
}
