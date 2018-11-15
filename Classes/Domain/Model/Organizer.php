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
 * This class contains all getter and setters for an Organizer.
 */
class Organizer extends AbstractEntity
{
    /**
     * Organizer.
     *
     * @var string
     */
    protected $organizer = '';

    /**
     * Link.
     *
     * @var \JWeiland\Events2\Domain\Model\Link
     */
    protected $link;

    /**
     * Returns the organizer.
     *
     * @return string $organizer
     */
    public function getOrganizer()
    {
        return $this->organizer;
    }

    /**
     * Sets the organizer.
     *
     * @param string $organizer
     */
    public function setOrganizer($organizer)
    {
        $this->organizer = (string)$organizer;
    }

    /**
     * Returns the link.
     *
     * @return Link $link
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Sets the link.
     *
     * @param Link $link
     */
    public function setLink(Link $link)
    {
        $this->link = $link;
    }
}
