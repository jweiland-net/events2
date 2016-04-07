<?php

namespace JWeiland\Events2\Persistence;

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
class Join
{
    protected $join = '';
    protected $table = '';
    protected $leftField = '';
    protected $rightField = '';

    /**
     * @param string $join
     *
     * @return \JWeiland\Events2\Persistence\Join
     */
    public function setJoin($join)
    {
        $this->join = $join;

        return $this;
    }

    /**
     * @return string
     */
    public function getJoin()
    {
        return $this->join;
    }

    /**
     * Set the uid field of the foreign table.
     *
     * @param string $leftField
     *
     * @return \JWeiland\Events2\Persistence\Join
     */
    public function setLeftField($leftField)
    {
        $this->leftField = $leftField;

        return $this;
    }

    /**
     * Get the uid field of the foreign table.
     *
     * @return string
     */
    public function getLeftField()
    {
        return $this->leftField;
    }

    /**
     * Set the uid field of the local table
     * In most cases it should be the field of the table defined in $this->table.
     *
     * @param string $rightField
     *
     * @return \JWeiland\Events2\Persistence\Join
     */
    public function setRightField($rightField)
    {
        $this->rightField = $rightField;

        return $this;
    }

    /**
     * Get the uid field of the local table.
     *
     * @return string
     */
    public function getRightField()
    {
        return $this->rightField;
    }

    /**
     * Set the table to join with.
     *
     * @param string $table
     *
     * @return \JWeiland\Events2\Persistence\Join
     */
    public function setTable($table)
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Get the table to join with.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * build sql string for join.
     *
     * @return string
     */
    public function buildSql()
    {
        return $this->getJoin().' '.$this->getTable().' ON '.$this->getLeftField().'='.$this->getRightField();
    }
}
