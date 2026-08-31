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

        if ($result['command'] !== 'new') {
            return $result;
        }

        try {
            // Since TYPO3 14 a "type => datetime" column has to carry a
            // \DateTimeInterface. An integer timestamp makes DatetimeElement
            // throw #1731132127 and the "new event" form ends in HTTP 500.
            // The column is declared as "format => date", so normalize to midnight.
            $now = $this->context->getPropertyFromAspect('date', 'full');
            if ($now instanceof \DateTimeInterface) {
                $result['databaseRow']['event_begin'] = \DateTimeImmutable::createFromInterface($now)
                    ->setTime(0, 0, 0);
            }
        } catch (AspectNotFoundException) {
        }

        return $result;
    }
}
