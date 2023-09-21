<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Hooks\Solr;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Result\SearchResult;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSetProcessor;
use ApacheSolrForTypo3\Solr\GarbageCollector;
use JWeiland\Events2\Service\EventService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * Remove event records from result set, if they are not current anymore.
 */
class ResultsCommandHook implements SearchResultSetProcessor
{
    /**
     * @var EventService
     */
    protected $eventService;

    /**
     * Do not add GarbageCollector, as DI autowire wont find the file, if solr is not installed
     */
    public function __construct(EventService $eventService)
    {
        $this->eventService = $eventService;
    }

    /**
     * Remove event records from result set, if they are not current anymore.
     * Modifies the given document and returns the modified document as result.
     *
     * @param SearchResultSet $resultSet
     * @return SearchResultSet
     */
    public function process(SearchResultSet $resultSet): SearchResultSet
    {
        if ($resultSet->getAllResultCount() === 0) {
            // when the search does not produce a ResultSet, do nothing
            return $resultSet;
        }

        /** @var SearchResult $searchResult */
        $searchResults = $resultSet->getSearchResults()->getArrayCopy();
        foreach ($searchResults as $key => $searchResult) {
            $uidField = $searchResult['uid'];
            $typeField = $searchResult['type'];
            if ($typeField === 'tx_events2_domain_model_event') {
                $nextDate = $this->eventService->getNextDayForEvent((int)$uidField);
                if (!$nextDate instanceof \DateTimeImmutable) {
                    /** @var GarbageCollector $garbageCollector */
                    $garbageCollector = GeneralUtility::makeInstance(GarbageCollector::class);
                    $garbageCollector->collectGarbage('tx_events2_domain_model_event', (int)$uidField);
                    unset($resultSet->getSearchResults()[$key]);
                } else {
                    $searchResult->setField('nextDay', (int)$nextDate->format('U'));
                    $resultSet->getSearchResults()[$key] = $searchResult;
                }
            }
        }

        return $resultSet;
    }
}
