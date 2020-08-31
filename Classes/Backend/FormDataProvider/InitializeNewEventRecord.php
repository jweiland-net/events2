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

/*
 * This class sets some dynamic default values (like event_begin) for event record
 */
class InitializeNewEventRecord implements FormDataProviderInterface
{
    /**
     * Prefill event_begin with current date
     *
     * @param array $result Initialized result array
     * @return array Do not add as strict type because of Interface
     */
    public function addData(array $result)
    {
        if ($result['tableName'] !== 'tx_events2_domain_model_event') {
            return $result;
        }

        if ($result['command'] === 'new') {
            $result['databaseRow']['event_begin'] = $GLOBALS['EXEC_TIME'];
        }

        return $result;
    }
}
