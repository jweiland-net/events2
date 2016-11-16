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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class CreateUpdateDays
{
    /**
     * @var \JWeiland\Events2\Service\DayRelations
     */
    protected $dayRelations;
    
    /**
     * @var \JWeiland\Events2\Utility\EventUtility
     */
    protected $eventUtility;
    
    /**
     * initializes this object.
     */
    public function init()
    {
        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        $this->dayRelations = $objectManager->get('JWeiland\\Events2\\Service\\DayRelations');
        $this->eventUtility = $objectManager->get('JWeiland\\Events2\\Utility\\EventUtility');
    }

    /**
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $pObj
     */
    public function processDatamap_afterAllOperations(DataHandler $pObj)
    {
        // return if unexpected table
        if (!array_key_exists('tx_events2_domain_model_event', $pObj->datamap)) {
            return;
        }

        $this->init();

        $event = $this->eventUtility->getFullEventRecord(
            $this->getRealUid(
                key($pObj->datamap['tx_events2_domain_model_event']),
                $pObj
            )
        );
        $this->dayRelations->createDayRelations($event);
    }
    
    /**
     * if a record was new its uid is not an int. It's a string starting with "NEW"
     * This method returns the real uid as int.
     *
     * @param string                                   $uid
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
     *
     * @return int
     */
    protected function getRealUid($uid, DataHandler $dataHandler)
    {
        if (GeneralUtility::isFirstPartOfStr($uid, 'NEW')) {
            $uid = $dataHandler->substNEWwithIDs[$uid];
        }
        return $uid;
    }
}
