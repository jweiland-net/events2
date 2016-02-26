<?php

namespace JWeiland\Events2\Persistence\Mapper;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
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
 * A factory for a data map to map a single table configured in $TCA on a domain object.
 *
 * @version $ID:$
 */
class DataMapFactory extends \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory
{
    /**
     * This method tries to determine the type of type of relation to other tables and sets it based on
     * the $TCA column configuration.
     *
     * @param \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap $columnMap           The column map
     * @param string                                                  $columnConfiguration The column configuration from $TCA
     * @param array                                                   $propertyMetaData    The property metadata as delivered by the reflection service
     */
    protected function setRelations(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap $columnMap, $columnConfiguration, $propertyMetaData)
    {
        if (isset($columnConfiguration)) {
            if (isset($columnConfiguration['MM'])) {
                $columnMap = $this->setManyToManyRelation($columnMap, $columnConfiguration);
            } elseif (isset($propertyMetaData['elementType'])) {
                $columnMap = $this->setOneToManyRelation($columnMap, $columnConfiguration);
            } elseif (isset($propertyMetaData['type']) && strpos($propertyMetaData['type'], '_') !== false) {
                $columnMap = $this->setOneToOneRelation($columnMap, $columnConfiguration);
            } else {
                $columnMap->setTypeOfRelation(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_NONE);
            }
        } else {
            $columnMap->setTypeOfRelation(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_NONE);
        }

        return $columnMap;
    }

    /**
     * This method sets the configuration for a m:n relation based on
     * the $TCA column configuration.
     *
     * @param \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap $columnMap           The column map
     * @param array                                                   $columnConfiguration The column configuration from $TCA
     *
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedRelationException
     *
     * @return \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap
     */
    protected function setManyToManyRelation(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap $columnMap, $columnConfiguration)
    {
        if (isset($columnConfiguration['MM'])) {
            $columnMap->setTypeOfRelation(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY);
            $columnMap->setChildTableName($columnConfiguration['foreign_table']);
            $columnMap->setChildTableWhereStatement($columnConfiguration['foreign_table_where']);
            $columnMap->setRelationTableName($columnConfiguration['MM']);
            if (is_array($columnConfiguration['MM_match_fields'])) {
                $columnMap->setRelationTableMatchFields($columnConfiguration['MM_match_fields']);
            }
            if (is_array($columnConfiguration['MM_insert_fields'])) {
                $columnMap->setRelationTableInsertFields($columnConfiguration['MM_insert_fields']);
            }
            $columnMap->setRelationTableWhereStatement($columnConfiguration['MM_table_where']);
            if (!empty($columnConfiguration['MM_opposite_field'])) {
                $columnMap->setParentKeyFieldName('uid_foreign');
                $columnMap->setChildKeyFieldName('uid_local');
                $columnMap->setChildSortByFieldName('sorting_foreign');
            } else {
                $columnMap->setParentKeyFieldName('uid_local');
                $columnMap->setChildKeyFieldName('uid_foreign');
                $columnMap->setChildSortByFieldName('sorting');
            }
        } else {
            throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedRelationException('The given information to build a many-to-many-relation was not sufficient. Check your TCA definitions. mm-relations with IRRE must have at least a defined "MM" or "foreign_selector".', 1268817963);
        }
        if ($this->getControlSection($columnMap->getRelationTableName()) !== null) {
            $columnMap->setRelationTablePageIdColumnName('pid');
        }

        return $columnMap;
    }
}
