<?php
namespace JWeiland\Events2\Persistence\Typo384\Generic\Storage;

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

use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * Overwritten QueryParser with a little implementation of GROUP BY
 */
class Typo3DbQueryParser extends \TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser
{
    /**
     * Returns a ready to be executed QueryBuilder object, based on the query
     *
     * @param QueryInterface $query
     * @return QueryBuilder
     */
    public function convertQueryToDoctrineQueryBuilder(QueryInterface $query)
    {
        /** @var \JWeiland\Events2\Persistence\Typo384\Generic\Query $query */
        // Reset all properties
        $this->tablePropertyMap = [];
        $this->tableAliasMap = [];
        $this->unionTableAliasCache = [];
        $this->tableName = '';
        // Find the right table name
        $source = $query->getSource();
        $this->initializeQueryBuilder($source);

        $constraint = $query->getConstraint();
        if ($constraint instanceof Qom\ConstraintInterface) {
            $wherePredicates = $this->parseConstraint($constraint, $source);
            if (!empty($wherePredicates)) {
                $this->queryBuilder->andWhere($wherePredicates);
            }
        }

        $this->parseGroupings($query->getGroupings(), $source);
        $this->parseOrderings($query->getOrderings(), $source);
        $this->addTypo3Constraints($query);

        // override select fields, if we have set them manually
        $this->queryBuilder->select($query->getSelect());

        return $this->queryBuilder;
    }

    /**
     * Transforms groupings into SQL.
     *
     * @param array $groupings An array of groupings
     * @param Qom\SourceInterface $source The source
     *
     * @return void
     */
    protected function parseGroupings(array $groupings, Qom\SourceInterface $source)
    {
        foreach ($groupings as $propertyName) {
            $className = null;
            $tableName = '';
            $columnName = '';
            if (!preg_match('@^[a-zA-Z0-9\.]+$@', $propertyName)) {
                // a special MySQL part
                $columnName = $propertyName;
            } elseif ($source instanceof Qom\SelectorInterface) {
                $className = $source->getNodeTypeName();
                $tableName = $this->dataMapper->convertClassNameToTableName($className);
                $fullPropertyPath = '';
                while (strpos($propertyName, '.') !== false) {
                    $this->addUnionStatement($className, $tableName, $propertyName, $fullPropertyPath);
                }
                $columnName = $this->dataMapper->convertPropertyNameToColumnName($propertyName, $className);
            } elseif ($source instanceof Qom\JoinInterface) {
                $tableName = $source->getLeft()->getSelectorName();
                $columnName = $this->dataMapper->convertPropertyNameToColumnName($propertyName, $className);
            }
            if ($tableName !== '') {
                $this->queryBuilder->addGroupBy($tableName . '.' . $columnName);
            } else {
                $this->queryBuilder->addGroupBy($columnName);
            }
        }
    }
}
