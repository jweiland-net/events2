<?php
namespace JWeiland\Events2\Persistence\Typo376\Generic;

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
 * Overwritten Query class with a little implementation of GROUP BY
 */
class Query extends \TYPO3\CMS\Extbase\Persistence\Generic\Query
{
    /**
     * @var array
     */
    protected $select = array();

    /**
     * @var array
     */
    protected $groupings = array();

    /**
     * Returns the select
     *
     * @return array $select
     */
    public function getSelect()
    {
        return $this->select;
    }

    /**
     * Sets the select
     *
     * @param array $select
     *
     * @return void
     */
    public function setSelect($select)
    {
        $this->select = $select;
    }

    /**
     * Returns the groupings
     *
     * @return array $groupings
     */
    public function getGroupings()
    {
        return $this->groupings;
    }

    /**
     * Sets the groupings
     * No array keys. Just the property name
     *
     * @param array $groupings
     *
     * @return Query
     */
    public function setGroupings(array $groupings)
    {
        $this->groupings = $groupings;
        return $this;
    }
}
