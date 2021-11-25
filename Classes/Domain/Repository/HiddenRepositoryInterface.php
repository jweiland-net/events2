<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Repository;

/*
 * Interface to identify Repositories which can find hidden objects
 * Currently used in HiddenObjectsHelper
 */

use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;

interface HiddenRepositoryInterface
{
    /**
     * Find object by a given property value whether it is hidden or not.
     *
     * @param mixed $value The Value to compare against $property
     */
    public function findHiddenObject($value, string $property = 'uid'): ?AbstractDomainObject;
}
