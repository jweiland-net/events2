<?php
declare(strict_types = 1);
namespace JWeiland\Events2\Domain\Model;

/*
 * This file is part of the events2 project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
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

    /**
     * @return int|null
     */
    public function getOrganizer()
    {
        return $this->organizer;
    }

    /**
     * @param int|null $organizer
     */
    public function setOrganizer($organizer = null)
    {
        $this->organizer = $organizer;
    }
}
