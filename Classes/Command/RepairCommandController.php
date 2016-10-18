<?php

namespace JWeiland\Events2\Command;

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
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;
use JWeiland\Events2\Service\DayRelations;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class RepairCommandController extends CommandController
{
    /**
     * @var \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected $databaseConnection;

    /**
     * @var \JWeiland\Events2\Utility\DateTimeUtility
     */
    protected $dateTimeUtility;
    
    /**
     * Needed to wrap activity bar:
     * ...........F.......
     * ....N....S.........
     *
     * @var int
     */
    protected $rowCounter = 0;

    /**
     * inject DateTime Utility.
     *
     * @param DateTimeUtility $dateTimeUtility
     */
    public function injectDateTimeUtility(DateTimeUtility $dateTimeUtility)
    {
        $this->dateTimeUtility = $dateTimeUtility;
    }

    /**
     * initializes this object.
     */
    public function initializeObject()
    {
        $this->databaseConnection = $GLOBALS['TYPO3_DB'];
    }

    /**
     * Repair events.
     */
    public function eventsCommand()
    {
        $this->outputLine('Start repairing day records of events');
    
        $this->outputLine('');
        $this->truncateDayTable();
        $this->outputLine('');
        $this->reGenerateDayRelations();
        $this->outputLine('');
        $this->updateReferenceIndex();
    }
    
    /**
     * Truncate day table. We will build them up again within the next steps
     *
     * @return void
     */
    protected function truncateDayTable()
    {
        $this->databaseConnection->exec_TRUNCATEquery('tx_events2_domain_model_day');
        $this->outputLine(PHP_EOL . 'I have truncated the day table');
    }
    
    /**
     * After solving bugs in DayGenerator it would be good to recreate all days for events
     *
     * @return void
     */
    protected function reGenerateDayRelations()
    {
        $counter = 0;
        $this->rowCounter = 0;
    
        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        /** @var DayRelations $dayRelations */
        $dayRelations = $objectManager->get('JWeiland\\Events2\\Service\\DayRelations');
    
        // get all event records
        $rows = $this->databaseConnection->exec_SELECTgetRows(
            '*',
            'tx_events2_domain_model_event',
            '1=1'
        );
    
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $dayRelations->createDayRelations($row);
                $this->echoValue();
                ++$counter;
            }
        }
        $this->outputLine(PHP_EOL . 'We have recreated the day records for ' . $counter . ' event records.');
    }
    
    /**
     * Update TYPO3s Reference index
     *
     * @return void
     */
    protected function updateReferenceIndex()
    {
        $counter = 0;
        $this->rowCounter = 0;
        $this->outputLine('Update reference index.');
    
        // get all event records
        $rows = $this->databaseConnection->exec_SELECTgetRows(
            'uid',
            'tx_events2_domain_model_event',
            '1=1'
        );
        if (!empty($rows)) {
            /** @var \TYPO3\CMS\Core\Database\ReferenceIndex $refObject */
            $refObject = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\ReferenceIndex');
        
            foreach ($rows as $row) {
                $refObject->updateRefIndexTable('tx_events2_domain_model_event', $row['uid']);
                $this->echoValue();
                ++$counter;
            }
        }
        $this->outputLine(PHP_EOL . 'I have updated ' . $counter . ' reference indexes for events.');
    }
    
    /**
     * echo "whatEver"
     *
     * @param string $value
     * @param boolean $reset
     * @return void
     */
    protected function echoValue($value = '.', $reset = false)
    {
        if ($reset) $this->rowCounter = 0;
        if ($this->rowCounter < 40) {
            echo $value;
            $this->rowCounter++;
        } else {
            echo PHP_EOL . $value;
            $this->rowCounter = 1;
        }
    }
}

