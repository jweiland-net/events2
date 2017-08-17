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
use JWeiland\Events2\Service\DayRelationService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\ProgressProviderInterface;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ReGenerateDays extends AbstractTask implements ProgressProviderInterface
{
    /**
     * @var DatabaseConnection
     */
    protected $databaseConnection;

    /**
     * @var DayRelationService
     */
    protected $dayRelations;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * constructor of this class.
     */
    public function __construct()
    {
        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        $this->dayRelations = $objectManager->get('JWeiland\\Events2\\Service\\DayRelationService');
        $this->registry = $objectManager->get('TYPO3\\CMS\\Core\\Registry');
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
        $this->registry->removeAllByNamespace('events2TaskCreateUpdate');

        $events = BackendUtility::getRecordsByField(
            'tx_events2_domain_model_event',
            'deleted',
            '0',
            'AND (
              (event_type = \'single\' AND event_begin > UNIX_TIMESTAMP())
              OR (event_type = \'duration\' AND (event_end = 0 OR event_end > UNIX_TIMESTAMP()))
              OR (event_type = \'recurring\' AND (recurring_end = 0 OR recurring_end > UNIX_TIMESTAMP()))
            )'
        );

        if (!empty($events)) {
            $counter = 0;
            foreach ($events as $event) {
                $counter++;
                $this->registry->set('events2TaskCreateUpdate', 'info', array(
                    'uid' => $event['uid'],
                    'pid' => $event['pid'],
                    'type' => $event['event_type']
                ));
                $this->dayRelations->createDayRelations($event['uid']);
                $this->registry->set('events2TaskCreateUpdate', 'progress', array(
                    'records' => count($events),
                    'counter' => $counter
                ));
            }
        }

        $this->registry->remove('events2TaskCreateUpdate', 'info');

        return true;
    }

    /**
     * This method is designed to return some additional information about the task,
     * that may help to set it apart from other tasks from the same class
     * This additional information is used - for example - in the Scheduler's BE module
     * This method should be implemented in most task classes
     *
     * @return string Information to display
     */
    public function getAdditionalInformation()
    {
        $content = '';
        $info = $this->registry->get('events2TaskCreateUpdate', 'info');
        if ($info) {
            $content = sprintf(
                'Current event: uid: %d, pid: %d, type: %s.',
                $info['uid'],
                $info['pid'],
                $info['type']
            );
            if ($info['type'] === 'recurring') {
                $content .= ' Events of type recurring needs much longer to process as they can have hundreds of day records';
            }
        }
        return $content;
    }

    /**
     * Gets the progress of a task.
     *
     * @return float Progress of the task as a two decimal precision float. f.e. 44.87
     */
    public function getProgress() {
        $progress = $this->registry->get('events2TaskCreateUpdate', 'progress');
        if ($progress) {
            return 100 / $progress['records'] * $progress['counter'];
        } else {
            return 0.0;
        }
    }
}
