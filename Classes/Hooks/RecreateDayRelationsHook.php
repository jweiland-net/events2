<?php

namespace JWeiland\Events2\Hooks;

/*
 * This file is part of the events2 project.
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
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class DataHandlerHook
 *
 */
class RecreateDayRelationsHook
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
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
    }

    /**
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
     * @return void
     * @throws \Exception
     */
    public function processDatamap_afterAllOperations($dataHandler)
    {
        if (array_key_exists('tx_events2_domain_model_event', $dataHandler->datamap)) {
            foreach ($dataHandler->datamap['tx_events2_domain_model_event'] as $eventUid => $eventRecord) {
                $this->addDayRelationsForEvent($this->getRealUid($eventUid, $dataHandler));
            }
        }
    }

    /**
     * Add day relations to event
     *
     * @param int $eventUid
     * @return void
     * @throws \Exception
     */
    protected function addDayRelationsForEvent($eventUid)
    {
        /** @var DayRelationService $dayRelationService */
        $dayRelationService = $this->objectManager->get(DayRelationService::class);
        $dayRelationService->createDayRelations($eventUid);
    }

    /**
     * If a record was new, its uid is not an int. It's a string starting with "NEW"
     * This method returns the real uid as int.
     *
     * @param string $uid
     * @param DataHandler $dataHandler
     * @return int
     */
    protected function getRealUid($uid, $dataHandler)
    {
        if (GeneralUtility::isFirstPartOfStr($uid, 'NEW')) {
            $uid = $dataHandler->substNEWwithIDs[$uid];
        }
        return (int)$uid;
    }
}
