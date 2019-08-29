<?php

namespace JWeiland\Events2\Hooks\Solr;

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
use ApacheSolrForTypo3\Solr\IndexQueue\Item;
use ApacheSolrForTypo3\Solr\IndexQueue\PageIndexerDocumentsModifier;
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Service\EventService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * As we can't create a SQL Query with JOIN in Solr configuration,
 * we have to remove invalid documents on our own here
 */
class IndexerHook implements PageIndexerDocumentsModifier
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
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->eventService = $objectManager->get(EventService::class);
    }

    /**
     * Modifies the given documents
     * I don't know when an event ends, as the user can leave this field empty.
     * So I don't see any chance to set Solr field "expires" correctly.
     * My idea for now is: Check for each event, if a next day record can be created. If so: keep it in Solr, else remove it.
     *
     * @param Item $item The currently being indexed item.
     * @param int $language The language uid of the documents
     * @param array $documents An array of documents to be indexed
     * @return array An array of modified documents
     */
    public function modifyDocuments(Item $item, $language, array $documents)
    {
        if ($item->getType() === 'tx_events2_domain_model_event') {
            $nextDate = $this->eventService->getNextDayForEvent((int)$item->getRecordUid());
            if ($nextDate === false || !$nextDate instanceof \DateTime) {
                // clear document array, if there are no further dates in future
                $documents = [];
            }
        }
        return $documents;
    }
}
