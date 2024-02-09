<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Traits;

use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

/**
 * Trait to inject PersistenceManager. Mostly used in controllers.
 */
trait InjectPersistenceManagerTrait
{
    protected PersistenceManagerInterface $persistenceManager;

    public function injectPersistenceManager(PersistenceManagerInterface $persistenceManager): void
    {
        $this->persistenceManager = $persistenceManager;
    }
}
