<?php
namespace JWeiland\Events2;

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
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;

/**
 * Update class for the extension manager.
 */
class ext_update
{
    /**
     * Array of flash messages (params) array[][status,title,message]
     *
     * @var array
     */
    protected $messageArray = array();

    /**
     * @var FlexFormTools
     */
    protected $flexFormTools;

    /**
     * Main update function called by the extension manager.
     *
     * @return string
     */
    public function main()
    {
        $this->processUpdates();
        return $this->generateOutput();
    }

    /**
     * Called by the extension manager to determine if the update menu entry
     * should by showed.
     *
     * @return bool
     */
    public function access()
    {
        $amountOfRecords = $this->getDatabaseConnection()->exec_SELECTcountRows(
            '*',
            'tx_events2_domain_model_event',
            'event_type=' . $this->getDatabaseConnection()->fullQuoteStr('', 'tx_events2_domain_model_event')
        );
        return (bool)$amountOfRecords;
    }

    /**
     * The actual update function. Add your update task in here.
     *
     * @return void
     */
    protected function processUpdates()
    {
        $this->migrateToVersion200();
    }
    
    /**
     * migrate to version 2.0.0
     *
     * @return void
     */
    protected function migrateToVersion200()
    {
        // checkbox recurring has been removed
        $rows = $this->getDatabaseConnection()->exec_SELECTgetRows(
            'uid, recurring_event, event_end',
            'tx_events2_domain_model_event',
            'event_type=' . $this->getDatabaseConnection()->fullQuoteStr('', 'tx_events2_domain_model_event')
        );
        if (empty($rows)) {
            // everything migrated or error, because recurring was already deleted
            $this->messageArray[] = array(
                FlashMessage::WARNING,
                'Update warning',
                'An error occurred while migration'
            );
            return null;
        }
        foreach ($rows as $key => $row) {
            $this->getDatabaseConnection()->exec_UPDATEquery(
                'tx_events2_domain_model_event',
                'uid=' . (int)$row['uid'],
                array(
                    'event_type' => $row['recurring_event'] ? 'recurring' : 'single',
                    'recurring_end' => $row['event_end'],
                    'event_end' => $row['recurring_event'] ? 0 : $row['event_end']
                )
            );
        }
        $this->messageArray[] = array(
            FlashMessage::OK,
            'Update successful',
            sprintf(
                'We have updated %d event records',
                count($rows)
            )
        );
    }
    
    /**
     * Generates output by using flash messages
     *
     * @return string
     */
    protected function generateOutput()
    {
        $output = '';
        foreach ($this->messageArray as $messageItem) {
            /** @var \TYPO3\CMS\Core\Messaging\FlashMessage $flashMessage */
            $flashMessage = GeneralUtility::makeInstance(
                'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                $messageItem[2],
                $messageItem[1],
                $messageItem[0]);
            $output .= $flashMessage->render();
        }
        return $output;
    }

    /**
     * Get TYPO3s Database Connection
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * Get TYPO3s FlexFormTools
     *
     * @return FlexFormTools
     */
    protected function getFlexFormTools()
    {
        if (!$this->flexFormTools instanceof FlexFormTools) {
            $this->flexFormTools = GeneralUtility::makeInstance(
                'TYPO3\\CMS\\Core\\Configuration\\FlexForm\\FlexFormTools'
            );
        }
        return $this->flexFormTools;
    }
}
