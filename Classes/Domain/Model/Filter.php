<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/*
 * This class will not be persisted.
 * It's a helper to filter organizers by selectbox in frontend.
 *
 * Hint: After selecting an organizer and switching back to an empty organizer to show all events again
 * setOrganizer will be called with null.
 *
 * @ToDo: Use nullable strict type if we do not support TYPO3 8 anymore because of PHP 7.0
 */
class Filter extends AbstractEntity
{
    /**
     * @var int|null
     */
    protected $organizer;

    public function getOrganizer(): ?int
    {
        return $this->organizer;
    }

    public function setOrganizer(?int $organizer = null)
    {
        $this->organizer = $organizer;
    }
}
