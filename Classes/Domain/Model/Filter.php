<?php

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
 * This class will not be persisted. It's a helper to filter organizers by selectbox in frontend.
 */
class Filter extends AbstractEntity
{
    /**
     * Organizer.
     *
     * @var int
     */
    protected $organizer = 0;

    /**
     * Returns the organizer
     *
     * @return int $organizer
     */
    public function getOrganizer()
    {
        return $this->organizer;
    }

    /**
     * Sets the organizer
     *
     * @param int $organizer
     * @return void
     */
    public function setOrganizer($organizer)
    {
        $this->organizer = (int)$organizer;
    }
}
