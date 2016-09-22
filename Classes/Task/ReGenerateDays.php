<?php

namespace JWeiland\Events2\Task;

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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ReGenerateDays extends AbstractTask
{
    /**
     * @var \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected $databaseConnection = null;

    /**
     * @var \JWeiland\Events2\Service\DayRelations
     */
    protected $dayRelations = null;

    /**
     * constructor of this class.
     */
    public function __construct()
    {
        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        $this->dayRelations = $objectManager->get('JWeiland\\Events2\\Service\\DayRelations');
        $this->databaseConnection = $GLOBALS['TYPO3_DB'];
        parent::__construct();
    }

    /**
     * This is the main method that is called when a task is executed
     * Note that there is no error handling, errors and failures are expected
     * to be handled and logged by the client implementations.
     * Should return TRUE on successful execution, FALSE on error.
     *
     * @return bool Returns TRUE on successful execution, FALSE on error
     */
    public function execute()
    {
        // get all uids which we have to update
        $eventUids = $this->databaseConnection->exec_SELECTgetRows(
            'uid',
            'tx_events2_domain_model_event',
            '1=1'.
            BackendUtility::BEenableFields('tx_events2_domain_model_event').
            BackendUtility::deleteClause('tx_events2_domain_model_event')
        );

        // create/update days for each event
        foreach ($eventUids as $eventUid) {
            $this->dayRelations->createDayRelations(
                $this->getFullEventRecord($eventUid['uid'])
            );
        }

        return true;
    }

    /**
     * get full event record
     * While updating a record only the changed fields will be in $fieldArray.
     *
     * @param int $uid
     *
     * @return array
     */
    protected function getFullEventRecord($uid)
    {
        $event = BackendUtility::getRecord('tx_events2_domain_model_event', (int)$uid);
        if ($event['exceptions']) {
            $event['exceptions'] = $this->getExceptions($uid);
        }

        return $event;
    }

    /**
     * get Exceptions of specified event uid.
     *
     * @param $eventUid
     *
     * @return array
     */
    protected function getExceptions($eventUid)
    {
        $exceptions = $this->databaseConnection->exec_SELECTgetRows(
            'uid, exception_type, exception_date',
            'tx_events2_domain_model_exception',
            'event='.(int)$eventUid.
            BackendUtility::BEenableFields('tx_events2_domain_model_exception').
            BackendUtility::deleteClause('tx_events2_domain_model_exception')

        );

        // check, if error occurs
        if (!is_array($exceptions)) {
            return array();
        } else {
            return $exceptions;
        }
    }
}
