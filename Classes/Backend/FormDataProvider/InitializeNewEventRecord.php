<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace JWeiland\Events2\Backend\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;

/**
 * This class sets some dynamic default values for event record
 */
class InitializeNewEventRecord implements FormDataProviderInterface
{
    /**
     * Add form data to result array
     *
     * @param array $result Initialized result array
     * @return array Result filled with more data
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
