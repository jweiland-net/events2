<?php

namespace JWeiland\Events2\Tca;

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
use TYPO3\CMS\Core\DataHandling\DataHandler;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class DeleteDayRelations
{
    /**
     * @var \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected $databaseConnection = null;

    /**
     * initializes this object.
     */
    protected function init()
    {
        $this->databaseConnection = $GLOBALS['TYPO3_DB'];
    }

    /**
     * The delete process of TYPO3 deletes the event record itself and updates the reference index of the related records
     * BUT: It does not delete the records of MM-table.
     *
     * @param string      $table
     * @param int         $id
     * @param array       $event
     * @param bool        $recordWasDeleted
     * @param DataHandler $pObj
     *
     * @throws \Exception
     */
    public function processCmdmap_deleteAction($table, $id, array $event, $recordWasDeleted, DataHandler $pObj)
    {
        // return if unexpected table
        if ($table !== 'tx_events2_domain_model_event') {
            return;
        }

        // delete event if not already done
        if (!$recordWasDeleted) {
            // normally this was already done by DataHandlerHook
            $pObj->deleteEl($table, $id);
        }

        // register database connection
        $this->init();

        // get relations from event to days
        // this must be done before we delete the MM-Relations
        $days = $this->databaseConnection->exec_SELECTgetRows(
            'uid_foreign',
            'tx_events2_event_day_mm',
            'uid_local = '.(int) $event['uid']
        );
        if ($days === null) {
            throw new \Exception('SQL-Error occurs while selecting related day records in DELETEDayRelations.php', 1421671032);
        }

        // delete relations from MM-table
        $this->deleteMMRelations($event['uid']);

        // updating the new amount of relations must be the last part
        $this->updateAmountOfEventsInDayRecords($days);
    }

    /**
     * update amount of related events in day record.
     *
     * @param array $days
     */
    protected function updateAmountOfEventsInDayRecords(array $days)
    {
        foreach ($days as $day) {
            // get amount of related day records
            $amount = $this->databaseConnection->exec_SELECTcountRows(
                '*',
                'tx_events2_event_day_mm',
                'uid_foreign = '.(int) $day['uid_foreign']
            );

            if ($amount) {
                // update value in day record
                $this->databaseConnection->exec_UPDATEquery(
                    'tx_events2_domain_model_day',
                    'uid = '.(int) $day['uid_foreign'],
                    array('events' => $amount)
                );
            } else {
                // if day record has no other relations to events anymore we can safely delete day record
                $this->databaseConnection->exec_DELETEquery(
                    'tx_events2_domain_model_day',
                    'uid = '.(int) $day['uid_foreign']
                );
            }
        }
    }

    /**
     * delete MM-relations between event and days.
     *
     * @param int $eventUid
     */
    protected function deleteMMRelations($eventUid)
    {
        $this->databaseConnection->exec_DELETEquery(
            'tx_events2_event_day_mm',
            'uid_local = '.(int) $eventUid
        );
    }
}
