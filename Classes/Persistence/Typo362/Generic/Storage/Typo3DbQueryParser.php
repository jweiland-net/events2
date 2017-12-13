<?php
namespace JWeiland\Events2\Persistence\Typo362\Generic\Storage;

/**
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

use TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedOrderException;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom;

/**
 * Overwritten QueryParser with a little implementation of GROUP BY
 */
class Typo3DbQueryParser extends \TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser
{
    /**
     * Preparses the query and returns the query's hash and the parameters
     *
     * @param QueryInterface $query The query
     * @return array the hash and the parameters
     */
    public function preparseQuery(QueryInterface $query)
    {
        /** @var \JWeiland\Events2\Persistence\Typo362\Generic\Query $query */
        list($parameters, $operators) = $this->preparseComparison($query->getConstraint());
        $hashPartials = [
            $query->getQuerySettings(),
            $query->getSource(),
            array_keys($parameters),
            $operators,
            $query->getOrderings(),
            $query->getGroupings(),
        ];
        $hash = md5(serialize($hashPartials));

        return [$hash, $parameters];
    }

    /**
     * Parses the query and returns the SQL statement parts.
     *
     * @param QueryInterface $query The query
     *
     * @return array The SQL statement parts
     *
     * @throws \Exception
     */
    public function parseQuery(QueryInterface $query)
    {
        /** @var \JWeiland\Events2\Persistence\Typo362\Generic\Query $query */
        $sql = [];
        $sql['keywords'] = [];
        $sql['tables'] = [];
        $sql['unions'] = [];
        $sql['fields'] = [];
        $sql['where'] = [];
        $sql['additionalWhereClause'] = [];
        $sql['groupings'] = [];
        $sql['orderings'] = [];
        $sql['limit'] = ((int)$query->getLimit() ?: null);
        $sql['offset'] = ((int)$query->getOffset() ?: null);
        $sql['tableAliasMap'] = [];
        $source = $query->getSource();
        $this->parseSource($source, $sql);
        $this->parseConstraint($query->getConstraint(), $source, $sql);
        $this->parseGroupings($query->getGroupings(), $source, $sql);
        $this->parseOrderings($query->getOrderings(), $source, $sql);

        $tableNames = array_unique(array_keys($sql['tables'] + $sql['unions']));
        foreach ($tableNames as $tableName) {
            if (is_string($tableName) && !empty($tableName)) {
                $this->addAdditionalWhereClause($query->getQuerySettings(), $tableName, $sql);
            }
        }

        // override select fields, if we have set them manually
        if (count($query->getSelect())) {
            $sql['fields'] = $query->getSelect();
        }

        return $sql;
    }

    /**
     * Transforms groupings into SQL.
     *
     * @param array $groupings An array of groupings
     * @param Qom\SourceInterface $source The source
     * @param array &$sql The query parts
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function parseGroupings(array $groupings, Qom\SourceInterface $source, array &$sql)
    {
        foreach ($groupings as $propertyName) {
            $className = '';
            $tableName = '';
            $columnName = '';
            if (!preg_match('@^[a-zA-Z0-9\.]+$@', $propertyName)) {
                // a special MySQL part
                $columnName = $propertyName;
            } elseif ($source instanceof Qom\SelectorInterface) {
                $className = $source->getNodeTypeName();
                $tableName = $this->dataMapper->convertClassNameToTableName($className);
                while (strpos($propertyName, '.') !== FALSE) {
                    $this->addUnionStatement($className, $tableName, $propertyName, $sql);
                }
                $columnName = $this->dataMapper->convertPropertyNameToColumnName($propertyName, $className);
            } elseif ($source instanceof Qom\JoinInterface) {
                $tableName = $source->getLeft()->getSelectorName();
                $columnName = $this->dataMapper->convertPropertyNameToColumnName($propertyName, $className);
            }
            if ($tableName !== '') {
                $sql['groupings'][] = $tableName . '.' . $columnName;
            } else {
                $sql['groupings'][] = $columnName;
            }
        }
    }
}
