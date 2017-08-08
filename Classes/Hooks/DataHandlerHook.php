<?php

namespace JWeiland\Events2\Hooks;

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
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class DataHandlerHook
 *
 * @package JWeiland\Events2\Hooks
 */
class DataHandlerHook
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * DataHandlerHook constructor.
     */
    public function __construct()
    {
        $this->objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
    }

    /**
     * @param DataHandler $dataHandler
     */
    public function processDatamap_beforeStart($dataHandler)
    {
        if (!array_key_exists('tx_events2_domain_model_event', $dataHandler->datamap)) {
            return;
        }

        foreach ($dataHandler->datamap['tx_events2_domain_model_event'] as $eventUid => $eventRecord) {
            if (MathUtility::canBeInterpretedAsInteger($eventUid)) {
                $this->registerDaysAsDeletedForEvent($dataHandler, $eventUid);
            }
            $this->addDayRelationsForEvent($dataHandler, $eventUid);
        }
    }

    /**
     * Register days as deleted in DataHandler
     *
     * @param DataHandler $dataHandler
     * @param int $eventUid
     *
     * @return void
     */
    protected function registerDaysAsDeletedForEvent($dataHandler, $eventUid)
    {
        // days would normally a comma seperated list of day UIDs. With setting this value to an empty string, we will delete day relations
        $dataHandler->datamap['tx_events2_domain_model_event'][(int)$eventUid]['days'] = '';

        // Delete day records with DataHandler, so that Solr and other extensions will be informed about that
        $daysForEvent = BackendUtility::getRecordsByField( 'tx_events2_domain_model_day', 'event', (int)$eventUid);
        if (!empty($daysForEvent)) {
            foreach ($daysForEvent as $day) {
                $dataHandler->cmdmap['tx_events2_domain_model_day'][$day['uid']]['delete'] = 1;
            }
        }
    }

    /**
     * Add day relations to event
     *
     * @param DataHandler $dataHandler
     * @param int|string $eventUid
     *
     * @return void
     */
    protected function addDayRelationsForEvent($dataHandler, $eventUid)
    {
        /** @var DayRelationService $dayRelationService */
        $dayRelationService = $this->objectManager->get('JWeiland\\Events2\\Service\\DayRelationService');
        $dayRelationService->createDayRelations($eventUid, $dataHandler);
    }
}
