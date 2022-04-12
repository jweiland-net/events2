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
 */
class Filter extends AbstractEntity
{
    protected ?int $organizer = 0;

    protected int $timestamp = 0;

    public function getOrganizer(): ?int
    {
        return $this->organizer;
    }

    public function setOrganizer(?int $organizer): void
    {
        $this->organizer = $organizer;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function setTimestamp(int $timestamp): void
    {
        $this->timestamp = $timestamp;
    }
}
