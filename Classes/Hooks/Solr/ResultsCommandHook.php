<?php

namespace JWeiland\Events2\Hooks\Solr;

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
use ApacheSolrForTypo3\Solr\Plugin\Results\ResultsCommand;
use ApacheSolrForTypo3\Solr\ResultDocumentModifier\ResultDocumentModifier;
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Service\EventService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ResultsCommandHook implements ResultDocumentModifier
{
    /**
     * @var EventService
     */
    protected $eventService;

    /**
     * ResultsCommandHook constructor.
     */
    public function __construct()
    {
        $objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        $this->eventService = $objectManager->get('JWeiland\\Events2\\Service\\EventService');
    }

    /**
     * Modifies the given document and returns the modified document as result.
     *
     * @param ResultsCommand $resultCommand The search result command
     * @param array $resultDocument Result document as array
     *
     * @return array The document with fields as array
     */
    public function modifyResultDocument(ResultsCommand $resultCommand, array $resultDocument)
    {
        if ($resultDocument['type'] === 'tx_events2_domain_model_event') {
            $day = $this->eventService->getNextDayForEvent((int)$resultDocument['uid']);
            if ($day instanceof Day) {
                $resultDocument['next_day'] = $day->getSortDayTime()->format('U');
            }
        }
        return $resultDocument;
    }
}
