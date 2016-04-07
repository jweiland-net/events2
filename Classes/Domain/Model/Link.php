<?php

namespace JWeiland\Events2\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Stefan Froemken <projects@jweiland.net>, jweiland.net
 *  
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Link extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
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
