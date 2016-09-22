<?php

namespace JWeiland\Events2\Domain\Model;
    
/*
 * This file is part of the TYPO3 CMS project.
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
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Link extends AbstractEntity
{
    /**
     * Link.
     *
     * @var string
     */
    protected $link = '';

    /**
     * Title.
     *
     * @var string
     */
    protected $title = 'Video';

    /**
     * Deleted.
     *
     * @var bool
     */
    protected $deleted = false;

    /**
     * Returns the link.
     *
     * @return string $link
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Sets the link.
     *
     * @param string $link
     */
    public function setLink($link)
    {
        $this->link = (string) $link;
    }

    /**
     * Returns the title.
     *
     * @return string $title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets the title.
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = (string) $title;
    }

    /**
     * Returns the deleted.
     *
     * @return bool $deleted
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * Sets the deleted.
     *
     * @param bool $deleted
     */
    public function setDeleted($deleted)
    {
        $this->deleted = (bool) $deleted;
    }
}
