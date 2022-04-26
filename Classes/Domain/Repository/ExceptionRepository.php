<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Repository;

/*
 * Repository to get exception records
 */
class ExceptionRepository extends AbstractRepository
{
    public const TABLE = 'tx_events2_domain_model_exception';

    public function getAllByEventRecord(array $eventRecord): array
    {
        if (!isset($eventRecord['uid'])) {
            return [];
        }

        $tableAlias = 'ex';
        $expressionBuilder = $this->getExpressionBuilder(self::TABLE);

        // event -> exception is an inline relation, so we have to use the original event UID for relation.
        $expressions = [
            $expressionBuilder->eq(
                $tableAlias . '.event',
                $eventRecord['uid']
            )
        ];

        return $this->getRecordsByExpression(
            self::TABLE,
            $tableAlias,
            $expressions
        );
    }

    public function getRecord(
        int $uid,
        array $select = ['*'],
        bool $includeHidden = false
    ): array {
        return $this->getRecordByUid(
            self::TABLE,
            'ex',
            $uid,
            $select,
            $includeHidden
        );
    }
}
