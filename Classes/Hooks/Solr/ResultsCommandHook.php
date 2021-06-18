<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Hooks\Solr;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Result\SearchResult as SearchResult80;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResult as SearchResult70;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSetProcessor;
use ApacheSolrForTypo3\Solr\GarbageCollector;
use JWeiland\Events2\Service\EventService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;

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
     *
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->eventService = $objectManager->get(EventService::class);
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

        if (version_compare(ExtensionManagementUtility::getExtensionVersion('solr'), '8.0.0', '>=')) {
            /** @var SearchResult80 $searchResult */
            $searchResults = $resultSet->getSearchResults()->getArrayCopy();
        } else {
            /** @var SearchResult70 $searchResult */
            $searchResults = $resultSet->getSearchResults();
        }

        foreach ($searchResults as $key => $searchResult) {
            $uidField = $searchResult->getField('uid');
            $typeField = $searchResult->getField('type');
            if ($typeField['value'] === 'tx_events2_domain_model_event') {
                $nextDate = $this->eventService->getNextDayForEvent((int)$uidField['value']);
                if (!$nextDate instanceof \DateTime) {
                    /** @var GarbageCollector $garbageCollector */
                    $garbageCollector = GeneralUtility::makeInstance(GarbageCollector::class);
                    $garbageCollector->collectGarbage('tx_events2_domain_model_event', (int)$uidField['value']);
                    if (version_compare(ExtensionManagementUtility::getExtensionVersion('solr'), '8.0.0', '>=')) {
                        $resultSet->getSearchResults()->offsetUnset($key);
                    } else {
                        unset($resultSet->getSearchResults()[$key]);
                    }
                } else {
                    $searchResult->setField('nextDay', (int)$nextDate->format('U'));
                    if (version_compare(ExtensionManagementUtility::getExtensionVersion('solr'), '8.0.0', '>=')) {
                        $resultSet->getSearchResults()->offsetSet($key, $searchResult);
                    } else {
                        $resultSet->getSearchResults()[$key] = $searchResult;
                    }
                }
            }
        }

        return $resultSet;
    }
}
