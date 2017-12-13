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
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

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
    protected $messageArray = [];

    /**
     * @var FlexFormTools
     */
    protected $flexFormTools;

    /**
     * Mapping for switchable controller actions
     * in FlexForms
     *
     * @var array
     */
    protected $scaMapping = [
        'Event->listLatest;Event->show;Day->show;Location->show;Video->show' => 'Day->listLatest;Day->show;Day->showByTimestamp;Location->show;Video->show',
        'Event->listToday;Event->show;Day->show;Location->show;Video->show' => 'Day->listToday;Day->show;Day->showByTimestamp;Location->show;Video->show',
        'Event->listRange;Event->show;Day->show;Location->show;Video->show' => 'Day->listThisWeek;Day->show;Day->showByTimestamp;Location->show;Video->show',
        'Event->listThisWeek;Event->show;Day->show;Location->show;Video->show' => 'Day->listRange;Day->show;Day->showByTimestamp;Location->show;Video->show'
    ];

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
        // check for old column "recurring"
        $amountOfRecords = $this->getDatabaseConnection()->exec_SELECTcountRows(
            '*',
            'tx_events2_domain_model_event',
            'event_type=' . $this->getDatabaseConnection()->fullQuoteStr('', 'tx_events2_domain_model_event')
        );
        $fields = $this->getDatabaseConnection()->admin_get_fields('tx_events2_domain_model_event');

        if (
            array_key_exists('recurring_event', $fields) &&
            $amountOfRecords > 0
        ) {
            // if recurring_event was already deleted in InstallTool, we can not update
            return true;
        }

        // Check for changed SCA in FlexForms
        foreach ($this->scaMapping as $oldValue => $newValue) {
            $where = sprintf(
                'pi_flexform LIKE %s',
                $this->getDatabaseConnection()->fullQuoteStr('%' . htmlspecialchars($oldValue) . '%', 'tt_content')
            );
            $amountOfRecords = $this->getDatabaseConnection()->exec_SELECTcountRows(
                '*',
                'tt_content',
                $where
            );
            if ($amountOfRecords > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * The actual update function. Add your update task in here.
     *
     * @return void
     */
    protected function processUpdates()
    {
        $this->migrateColRecurringToEventType();
        $this->migrateToNewSwitchableControllerActionsInFlexForms();
    }

    /**
     * migrate column recurring to one of event_type
     *
     * @return void
     */
    protected function migrateColRecurringToEventType()
    {
        // checkbox recurring has been removed
        $rows = $this->getDatabaseConnection()->exec_SELECTgetRows(
            'uid, recurring_event, event_end',
            'tx_events2_domain_model_event',
            'event_type=' . $this->getDatabaseConnection()->fullQuoteStr('', 'tx_events2_domain_model_event')
        );
        if (empty($rows)) {
            // everything migrated
            $this->messageArray[] = [
                FlashMessage::INFO,
                'Event records already migrated',
                'I have not found any event records for migration. Maybe you have started this script a second time.'
            ];
            return null;
        }
        foreach ($rows as $key => $row) {
            $this->getDatabaseConnection()->exec_UPDATEquery(
                'tx_events2_domain_model_event',
                'uid=' . (int)$row['uid'],
                [
                    'event_type' => $row['recurring_event'] ? 'recurring' : 'single',
                    'recurring_end' => $row['event_end'],
                    'event_end' => $row['recurring_event'] ? 0 : $row['event_end']
                ]
            );
        }
        $this->messageArray[] = [
            FlashMessage::OK,
            'Migrating "recurring" col was successful',
            sprintf(
                'We have updated %d event records',
                count($rows)
            )
        ];
    }

    /**
     * Migrate old FlexForm values for Switchable Controller Actions to the new one
     *
     * @return void
     */
    protected function migrateToNewSwitchableControllerActionsInFlexForms()
    {
        foreach ($this->scaMapping as $oldValue => $newValue) {
            $where = sprintf(
                'pi_flexform LIKE %s',
                $this->getDatabaseConnection()->fullQuoteStr('%' . htmlspecialchars($oldValue) . '%', 'tt_content')
            );
            $replace = sprintf(
                'REPLACE(pi_flexform, %s, %s)',
                $this->getDatabaseConnection()->fullQuoteStr(htmlspecialchars($oldValue), 'tt_content'),
                $this->getDatabaseConnection()->fullQuoteStr(htmlspecialchars($newValue), 'tt_content')
            );
            $this->getDatabaseConnection()->exec_UPDATEquery(
                'tt_content',
                $where,
                [
                    'pi_flexform' => $replace
                ],
                ['pi_flexform']
            );
        }
        $this->messageArray[] = [
            FlashMessage::OK,
            'Migrating SCA successful',
            'We have updated all related tt_content records'
        ];
    }

    /**
     * Generates output by using flash messages
     *
     * @return string
     *
     * @throws \Exception
     */
    protected function generateOutput()
    {
        /** @var FlashMessageService $flashMessageService */
        $flashMessageService = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessageService');
        $flashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();

        /** @var StandaloneView $view */
        $view = GeneralUtility::makeInstance('TYPO3\\CMS\\Fluid\\View\\StandaloneView');
        if (VersionNumberUtility::convertVersionNumberToInteger(TYPO3_branch) <= 6002000) {
            $view->setTemplateSource('<f:flashMessages />');
        } else {
            $view->setTemplateSource('<f:flashMessages queueIdentifier="core.template.flashMessages" />');
        }
        foreach ($this->messageArray as $messageItem) {
            /** @var \TYPO3\CMS\Core\Messaging\FlashMessage $flashMessage */
            $flashMessage = GeneralUtility::makeInstance(
                'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                $messageItem[2],
                $messageItem[1],
                $messageItem[0]
            );

            $flashMessageQueue->enqueue($flashMessage);
        }
        return $view->render();
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
