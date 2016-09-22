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

/**
 * FileReference
 */
class FileReference extends \TYPO3\CMS\Extbase\Domain\Model\FileReference
{
    /**
     * title
     *
     * @var string
     */
    protected $title = '';

    /**
     * cruserId
     *
     * @var int
     */
    protected $cruserId = 0;

    /**
     * uidLocal
     *
     * @var int
     */
    protected $uidLocal = 0;

    /**
     * tablenames
     *
     * @var string
     */
    protected $tablenames = '';

    /**
     * Returns the title
     *
     * @return string $title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets the title
     *
     * @param string $title
     * @return void
     */
    public function setTitle($title)
    {
        $this->title = (string)$title;
    }

    /**
     * Returns the cruserId
     *
     * @return int $cruserId
     */
    public function getCruserId()
    {
        return $this->cruserId;
    }

    /**
     * Sets the cruserId
     *
     * @param int $cruserId
     * @return void
     */
    public function setCruserId($cruserId)
    {
        $this->cruserId = (int)$cruserId;
    }

    /**
     * Returns the uidLocal
     *
     * @return int $uidLocal
     */
    public function getUidLocal()
    {
        return $this->uidLocal;
    }

    /**
     * Sets the uidLocal
     *
     * @param int $uidLocal
     * @return void
     */
    public function setUidLocal($uidLocal)
    {
        $this->uidLocal = (int)$uidLocal;
    }

    /**
     * Returns the tablenames
     *
     * @return string $tablenames
     */
    public function getTablenames()
    {
        return $this->tablenames;
    }

    /**
     * Sets the tablenames
     *
     * @param string $tablenames
     * @return void
     */
    public function setTablenames($tablenames)
    {
        $this->tablenames = (string)$tablenames;
    }
}
