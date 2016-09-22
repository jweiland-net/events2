<?php

namespace JWeiland\Events2\Persistence;
    
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
