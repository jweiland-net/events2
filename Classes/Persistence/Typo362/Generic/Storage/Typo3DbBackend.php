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
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\Statement;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\Exception\BadConstraintException;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * Overwritten storage with a little implementation of GROUP BY
 */
class Typo3DbBackend extends \TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbBackend
{
    /**
     * Creates the parameters for the query methods of the database methods in the TYPO3 core, from an array
     * that came from a parsed query.
     *
     * @param array $statementParts
     * @return array
     */
    protected function createQueryCommandParametersFromStatementParts(array $statementParts)
    {
        return [
            'selectFields' => implode(' ', $statementParts['keywords']) . ' ' . implode(',', $statementParts['fields']),
            'fromTable'    => implode(' ', $statementParts['tables']) . ' ' . implode(' ', $statementParts['unions']),
            'whereClause'  => (
                    !empty($statementParts['where']) ? implode('', $statementParts['where']) : '1=1'
                ) . (
                    !empty($statementParts['additionalWhereClause'])
                    ? ' AND ' . implode(' AND ', $statementParts['additionalWhereClause'])
                    : ''
                ),
            'groupBy'      => (!empty($statementParts['groupings']) ? implode(', ', $statementParts['groupings']) : ''),
            'orderBy'      => (!empty($statementParts['orderings']) ? implode(', ', $statementParts['orderings']) : ''),
            'limit'        => ($statementParts['offset'] ? $statementParts['offset'] . ', ' : '')
                . ($statementParts['limit'] ? $statementParts['limit'] : '')
        ];
    }

    /**
     * Fetches the rows directly from the database, not using prepared statement
     *
     * @param array $statementParts
     * @return array the result
     */
    protected function getRowsFromDatabase(array $statementParts)
    {
        $queryCommandParameters = $this->createQueryCommandParametersFromStatementParts($statementParts);
        $rows = $this->databaseHandle->exec_SELECTgetRows(
            $queryCommandParameters['selectFields'],
            $queryCommandParameters['fromTable'],
            $queryCommandParameters['whereClause'],
            $queryCommandParameters['groupBy'],
            $queryCommandParameters['orderBy'],
            $queryCommandParameters['limit']
        );
        $this->checkSqlErrors();

        return $rows;
    }

    /**
     * Fetches the rows from the database, using prepared statement
     *
     * @param array $statementParts
     * @param array $parameters
     * @return array the result
     */
    protected function getRowsFromPreparedDatabase(array $statementParts, array $parameters)
    {
        $queryCommandParameters = $this->createQueryCommandParametersFromStatementParts($statementParts);
        $preparedStatement = $this->databaseHandle->prepare_SELECTquery(
            $queryCommandParameters['selectFields'],
            $queryCommandParameters['fromTable'],
            $queryCommandParameters['whereClause'],
            $queryCommandParameters['groupBy'],
            $queryCommandParameters['orderBy'],
            $queryCommandParameters['limit']
        );

        $preparedStatement->execute($parameters);
        $rows = $preparedStatement->fetchAll();

        $preparedStatement->free();
        return $rows;
    }

    /**
     * Returns the number of tuples matching the query.
     *
     * @param QueryInterface $query
     *
     * @return int The number of matching tuples
     *
     * @throws BadConstraintException
     */
    public function getObjectCountByQuery(QueryInterface $query)
    {
        if ($query->getConstraint() instanceof Statement) {
            throw new BadConstraintException('Could not execute count on queries with a constraint of type TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\Statement', 1256661045);
        }

        list($statementParts) = $this->getStatementParts($query);
        $queryCommandParameters = $this->createQueryCommandParametersFromStatementParts($statementParts);

        $fields = '*';
        if (isset($statementParts['keywords']['distinct'])) {
            $fields = 'DISTINCT ' . reset($statementParts['tables']) . '.uid';
        }
        if (!empty($statementParts['groupings'])) {
            $separator = ',' . $this->databaseHandle->fullQuoteStr('-', $queryCommandParameters['fromTable']) . ',';
            $fields = 'DISTINCT CONCAT(' . implode($separator, $statementParts['groupings']) . ')';
        }

        $count = $this->databaseHandle->exec_SELECTcountRows(
            $fields,
            $queryCommandParameters['fromTable'],
            $queryCommandParameters['whereClause']
        );
        $this->checkSqlErrors();

        if ($statementParts['offset']) {
            $count -= $statementParts['offset'];
        }

        if ($statementParts['limit']) {
            $count = min($count, $statementParts['limit']);
        }

        return (int)max(0, $count);
    }
}
