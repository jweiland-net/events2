<?php

namespace JWeiland\Events2\Persistence;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Stefan Froemken <projects@jweiland.net>, jweiland.net
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
class QueryBuilder
{
    protected $tables = array();
    protected $fields = array();
    protected $joins = array();
    protected $where = array();
    protected $groupBy = array();
    protected $orderBy = array();
    protected $limit = array();

    /**
     * @param array $fields
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * @param string $tableName
     * @param array  $fields
     */
    public function addFieldsForTable($tableName, array $fields = array('*'))
    {
        $this->fields[$tableName] = $fields;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param string $groupBy
     */
    public function setGroupBy($groupBy)
    {
        $this->groupBy = $groupBy;
    }

    /**
     * @return string
     */
    public function getGroupBy()
    {
        return $this->groupBy;
    }

    /**
     * @param array $joins
     */
    public function setJoins(array $joins)
    {
        $this->joins = $joins;
    }

    /**
     * @param \JWeiland\Events2\Persistence\Join $join
     */
    public function addJoin(\JWeiland\Events2\Persistence\Join $join)
    {
        $this->joins[] = $join;
    }

    /**
     * @return array
     */
    public function getJoins()
    {
        return $this->joins;
    }

    /**
     * @param string $orderBy
     */
    public function setOrderBy($orderBy)
    {
        $this->orderBy = $orderBy;
    }

    /**
     * @return string
     */
    public function getOrderBy()
    {
        return $this->orderBy;
    }

    /**
     * @param array $tables
     */
    public function setTables(array $tables)
    {
        $this->tables = $tables;
    }

    /**
     * @param string $table
     */
    public function addTable($table)
    {
        $this->tables[] = $table;
    }

    /**
     * @return array
     */
    public function getTables()
    {
        return $this->tables;
    }

    /**
     * @param array $where
     */
    public function setWhere(array $where)
    {
        $this->where = $where;
    }

    /**
     * @param string $where
     */
    public function addWhere($where)
    {
        $this->where[] = $where;
    }

    /**
     * @return array
     */
    public function getWhere()
    {
        return $this->where;
    }

    /**
     * @param array $limit
     */
    public function setLimit(array $limit)
    {
        $this->limit = $limit;
    }

    /**
     * @return array
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * build SQL-Statement.
     *
     * @return string
     */
    public function buildQuery()
    {
        return $GLOBALS['TYPO3_DB']->SELECTquery(
            $this->buildSelect(),
            $this->buildFrom(),
            $this->buildWhere(),
            $this->buildGroupBy(),
            $this->buildOrderBy(),
            $this->buildLimit()
        );
    }

    /**
     * build string for SELECT.
     *
     * @return string
     */
    protected function buildSelect()
    {
        $sql = array();
        foreach ($this->getFields() as $tableName => $fields) {
            foreach ($fields as $fieldName) {
                $sql[] = $tableName.'.'.$fieldName;
            }
        }
        if (count($sql)) {
            return implode(', ', $sql);
        } else {
            return '';
        }
    }

    /**
     * build string for FROM.
     *
     * @return string
     */
    protected function buildFrom()
    {
        $sql = array();
        $sql[] = implode(', ', $this->getTables());

        // add JOINS
        if (count($this->getJoins())) {
            /** @var \JWeiland\Events2\Persistence\Join $join */
            foreach ($this->getJoins() as $join) {
                $sql[] = $join->buildSql();
            }
        }
        if (count($sql)) {
            return implode(' ', $sql);
        } else {
            return '';
        }
    }

    /**
     * build string for WHERE.
     *
     * @return string
     */
    protected function buildWhere()
    {
        if (count($this->getWhere())) {
            return implode(' AND ', $this->getWhere());
        } else {
            return '';
        }
    }

    /**
     * build string for GROUP BY.
     *
     * @return string
     */
    protected function buildGroupBy()
    {
        if (count($this->getGroupBy())) {
            return implode(', ', $this->getGroupBy());
        } else {
            return '';
        }
    }

    /**
     * build string for ORDER BY.
     *
     * @return string
     */
    protected function buildOrderBy()
    {
        return $this->getOrderBy();
    }

    /**
     * build string for LIMIT.
     *
     * @return string
     */
    protected function buildLimit()
    {
        if (count($this->getLimit())) {
            $limit = $this->getLimit();
            if (count($limit) == 1) {
                return $limit[0];
            }
            if (count($limit) == 2) {
                return $limit[0].', '.$limit[1];
            }
        } else {
            return '';
        }
    }
}
