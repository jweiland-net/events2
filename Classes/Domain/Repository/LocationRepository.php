<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Repository;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * The location repository is used to sort the locations in our create-new-form. Further, it will be used in
 * our event importer
 */
class LocationRepository extends Repository
{
    protected EventDispatcherInterface $eventDispatcher;

    protected $defaultOrderings = [
        'location' => QueryInterface::ORDER_ASCENDING,
    ];
}
