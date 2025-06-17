<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Backend\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;

/**
 * This class sets some dynamic default values (like event_begin) for event record
 */
readonly class InitializeNewEventRecord implements FormDataProviderInterface
{
    private const TABLE = 'tx_events2_domain_model_event';

    public function __construct(
        private Context $context,
    ) {}

    /**
     * Prefill the column "event_begin" with the current date
     */
    public function addData(array $result): array
    {
        if ($result['tableName'] !== self::TABLE) {
            return $result;
        }

        if ($result['command'] === 'new') {
            try {
                $result['databaseRow']['event_begin'] = $this->context->getPropertyFromAspect(
                    'date',
                    'timestamp',
                );
            } catch (AspectNotFoundException) {
            }
        }

        return $result;
    }
}
