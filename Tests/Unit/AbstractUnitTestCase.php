<?php

namespace JWeiland\Events2\Tests\Unit;

/*
 * This file is part of the events2 project.
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

use Doctrine\DBAL\Driver\Statement;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Abstract class with helping methods for UnitTests
 */
abstract class AbstractUnitTestCase extends UnitTestCase
{
    /**
     * Helper method for UnitTests
     * Let Database query return given $returnValue
     *
     * @param string $tableName
     * @param mixed $returnValue
     * @param array $expressions
     */
    protected function buildAssertionForDatabaseWithReturnValue($tableName, $returnValue, array $expressions = [['expr' => 'eq', ['expr' => 'in']]])
    {
        /** @var Statement|ObjectProphecy $statement */
        $statement = $this->prophesize(Statement::class);
        /** @var ExpressionBuilder|ObjectProphecy $expressionBuilder */
        $expressionBuilder = $this->prophesize(ExpressionBuilder::class);
        /** @var QueryBuilder|ObjectProphecy $queryBuilder */
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        /** @var ConnectionPool|ObjectProphecy $connectionPool */
        $connectionPool = $this->prophesize(ConnectionPool::class);

        $statement->fetch()->willReturn($returnValue);
        $statement->fetchAll()->willReturn($returnValue);
        $statement->fetchColumn()->willReturn($returnValue);

        foreach ($expressions as $expression) {
            if (!array_key_exists('expr', $expression)) {
                continue;
            }

            if (array_key_exists('field', $expression)) {
                $expressionBuilder->{$expression['expr']}($expression['field'], Argument::cetera())->willReturn('');
            } else {
                $expressionBuilder->{$expression['expr']}(Argument::cetera())->willReturn('');
            }

            if (array_key_exists('value', $expression)) {
                $queryBuilder->createNamedParameter($expression['value'], Argument::cetera());
            } else {
                $queryBuilder->createNamedParameter(Argument::cetera());
            }
        }

        $queryBuilder->select(Argument::cetera())->willReturn($queryBuilder->reveal());
        $queryBuilder->count(Argument::cetera())->willReturn($queryBuilder->reveal());
        $queryBuilder->from(Argument::cetera())->willReturn($queryBuilder->reveal());
        $queryBuilder->where(Argument::cetera())->willReturn($queryBuilder->reveal());
        $queryBuilder->expr(Argument::cetera())->willReturn($expressionBuilder->reveal());
        $queryBuilder->execute(Argument::cetera())->willReturn($statement->reveal());

        $connectionPool->getQueryBuilderForTable($tableName)->shouldBeCalled()->willReturn($queryBuilder);

        GeneralUtility::addInstance(ConnectionPool::class, $connectionPool->reveal());
    }
}