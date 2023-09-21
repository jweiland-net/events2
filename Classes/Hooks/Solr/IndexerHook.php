<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Hooks\Solr;

use ApacheSolrForTypo3\Solr\IndexQueue\Item;
use ApacheSolrForTypo3\Solr\IndexQueue\PageIndexerDocumentsModifier;
use JWeiland\Events2\Service\EventService;

/*
 * As we can't create a SQL Query with JOIN in Solr configuration,
 * we have to remove invalid documents on our own here
 */
class IndexerHook implements PageIndexerDocumentsModifier
{
    /**
     * @var EventService
     */
    protected $eventService;

    public function __construct(EventService $eventService)
    {
        $this->eventService = $eventService;
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
    public function modifyDocuments(Item $item, int $language, array $documents): array
    {
        if ($item->getType() === 'tx_events2_domain_model_event') {
            $nextDate = $this->eventService->getNextDayForEvent((int)$item->getRecordUid());
            if (!$nextDate instanceof \DateTimeImmutable) {
                // clear document array, if there are no further dates in future
                $documents = [];
            }
        }
        return $documents;
    }
}
