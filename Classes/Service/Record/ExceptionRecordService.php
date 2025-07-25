<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Service\Record;

use JWeiland\Events2\Traits\RelationHandlerTrait;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

class ExceptionRecordService
{
    use RelationHandlerTrait;

    private const TABLE = 'tx_events2_domain_model_exception';

    public function __construct(
        private readonly TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    public function findAllByEventUid(int $eventUid): array
    {
        $schema = $this->tcaSchemaFactory->get('tx_events2_domain_model_event');

        $relationHandler = $this->createRelationHandlerInstance();
        $relationHandler->initializeForField(
            'tx_events2_domain_model_event',
            $schema->getField('exceptions'),
            $eventUid,
        );

        $exceptionRecords = [];
        foreach ($relationHandler->getValueArray() as $exceptionUid) {
            if ($exceptionRecord = BackendUtility::getRecordWSOL(self::TABLE, (int)$exceptionUid)) {
                $exceptionRecords[] = $exceptionRecord;
            }
        }

        return $exceptionRecords;
    }
}
