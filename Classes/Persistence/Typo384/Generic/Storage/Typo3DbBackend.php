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
use Doctrine\DBAL\DBALException;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\Exception\BadConstraintException;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\Exception\SqlErrorException;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * Overwritten storage with a little implementation of GROUP BY
 */
class Typo3DbBackend extends \TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbBackend
{
    /**
     * Returns the number of tuples matching the query.
     *
     * @param QueryInterface $query
     * @return int The number of matching tuples
     * @throws BadConstraintException
     * @throws SqlErrorException
     */
    public function getObjectCountByQuery(QueryInterface $query)
    {
        if ($query->getConstraint() instanceof Qom\Statement) {
            throw new BadConstraintException('Could not execute count on queries with a constraint of type TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\Statement', 1256661045);
        }

        $statement = $query->getStatement();
        if ($statement instanceof Qom\Statement
            && !$statement->getStatement() instanceof QueryBuilder
        ) {
            $rows = $this->getObjectDataByQuery($query);
            $count = count($rows);
        } else {
            /** @var Typo3DbQueryParser $queryParser */
            $queryParser  = $this->objectManager->get(Typo3DbQueryParser::class);
            $queryBuilder = $queryParser
                ->convertQueryToDoctrineQueryBuilder($query)
                ->resetQueryPart('orderBy');

            $groupBy = $queryBuilder->getQueryPart('groupBy');
            if ($queryParser->isDistinctQuerySuggested()) {
                $source = $queryBuilder->getQueryPart('from')[0];
                // Tablename is already quoted for the DBMS, we need to treat table and field names separately
                $tableName = $source['alias'] ?: $source['table'];
                $fieldName = $queryBuilder->quoteIdentifier('uid');
                $queryBuilder
                    ->resetQueryPart('groupBy')
                    ->selectLiteral(sprintf('COUNT(DISTINCT %s.%s)', $tableName, $fieldName));
            } elseif ($groupBy) {
                $queryBuilder
                    ->resetQueryPart('groupBy')
                    ->selectLiteral('COUNT(DISTINCT ' . implode(',', $groupBy) . ')');
            } else {
                $queryBuilder->count('*');
            }

            try {
                $count = $queryBuilder->execute()->fetchColumn(0);
            } catch (DBALException $e) {
                throw new SqlErrorException($e->getPrevious()->getMessage(), 1472074379);
            }
            if ($query->getOffset()) {
                $count -= $query->getOffset();
            }
            if ($query->getLimit()) {
                $count = min($count, $query->getLimit());
            }
        }
        return (int)max(0, $count);
    }
}
