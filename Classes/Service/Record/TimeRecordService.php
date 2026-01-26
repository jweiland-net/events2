<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Service\Record;

class TimeRecordService
{
    use RecordServiceTrait;

    private const TABLE = 'tx_events2_domain_model_time';

    /**
     * If you activate $includeExceptionTimes be sure to have exceptionRecords in $eventRecord['exceptions']
     */
    public function getAllByEventRecord(array $eventRecord, bool $includeExceptionTimeRecords = false): array
    {
        if (!isset($eventRecord['uid'])) {
            return [];
        }

        $expressionBuilder = $this->getExpressionBuilder(self::TABLE);

        // event -> time is an inline relation, so we have to use the original event UID for the relation.
        $expressions = [
            $expressionBuilder->eq(
                'event',
                $eventRecord['uid'],
            ),
        ];

        $timeRecords = $this->getRecordsByExpression(
            self::TABLE,
            $expressions,
        );

        $timeRecords = array_map(static function ($timeRecord) use ($eventRecord): array {
            $timeRecord['event'] = $eventRecord;
            $timeRecord['exception'] = [];
            return $timeRecord;
        }, $timeRecords);

        if ($includeExceptionTimeRecords && ($eventRecord['exceptions'] ?? []) !== []) {
            foreach ($eventRecord['exceptions'] as $exceptionRecord) {
                array_push(
                    $timeRecords,
                    ...$this->getAllByExceptionRecord($exceptionRecord),
                );
            }
        }

        return $timeRecords;
    }

    public function getAllByExceptionRecord(array $exceptionRecord): array
    {
        if (!isset($exceptionRecord['uid'])) {
            return [];
        }

        $expressionBuilder = $this->getExpressionBuilder(self::TABLE);

        // exception -> time is an inline relation, so we have to use the original event UID for the relation.
        $expressions = [
            $expressionBuilder->eq(
                'exception',
                $exceptionRecord['uid'],
            ),
        ];

        $timeRecords = $this->getRecordsByExpression(
            self::TABLE,
            $expressions,
        );

        return array_map(static function ($timeRecord) use ($exceptionRecord): array {
            $timeRecord['event'] = [];
            $timeRecord['exception'] = $exceptionRecord;
            return $timeRecord;
        }, $timeRecords);
    }

    public function getRecord(
        int $uid,
        array $select = ['*'],
        bool $includeHidden = false,
    ): array {
        return $this->getRecordByUid(
            self::TABLE,
            $uid,
            $select,
            $includeHidden,
        );
    }
}
