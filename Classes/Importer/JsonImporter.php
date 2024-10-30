<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Importer;

use JWeiland\Events2\Domain\Model\Category;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Exception;
use JWeiland\Events2\Domain\Model\Link;
use JWeiland\Events2\Domain\Model\Location;
use JWeiland\Events2\Domain\Model\Organizer;
use JWeiland\Events2\Domain\Model\Time;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Reactions\Authentication\ReactionUserAuthentication;

/**
 * Import event records via JSON.
 * Use this importer, if you have data which represents events2 database structure. If you need something simpler try
 * XmlImporter which has a much simpler structure.
 */
class JsonImporter
{
    public function __construct(
        protected readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param array $eventsImportData This is the payload (JSON decoded data from request)
     */
    public function import(array $eventsImportData): bool
    {
        // Chunk event data for faster DataHandler processing
        foreach (array_chunk($eventsImportData, 10) as $eventsImportDataChunk) {
            try {
                $dataMap = [];
                $cmdMap = [];
                foreach ($eventsImportDataChunk as $eventImportData) {
                    $this->updateDataMapForEventImport($eventImportData, $dataMap, $cmdMap);
                }

                $dataHandler = $this->getDataHandler();
                $dataHandler->start($dataMap, [], $this->getBackendUser());
                $dataHandler->process_datamap();
                $dataHandler->process_cmdmap();
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                return false;
            }
        }

        $this->logger->info('We have processed ' . count($eventsImportData) . ' events');

        return true;
    }

    protected function getDataHandler(): DataHandler
    {
        return GeneralUtility::makeInstance(DataHandler::class);
    }

    private function getBackendUser(): ReactionUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function updateDataMapForEventImport(array $eventImportData, array &$dataMap, array &$cmdMap): void
    {
        $eventRecord = $this->getEventRecordByImportId($eventImportData['id']);
        $eventUid = is_array($eventRecord) ? $eventRecord['uid'] : $this->getUniqueIdForNewRecords();
        $dataMap['tx_events2_domain_model_event'][$eventUid] = [

        ];
    }

    /**
     * Get event record by import_id.
     * Will only return event records of default language
     */
    protected function getEventRecordByImportId(string $importId): ?array
    {
        $queryBuilder = $this->getQueryBuilderForTable('tx_events2_domain_model_event');
        try {
            $eventRecord = $queryBuilder
                ->select('*')
                ->from('tx_events2_domain_model_event')
                ->where(
                    $queryBuilder->expr()->eq(
                        'import_id',
                        $queryBuilder->createNamedParameter($importId, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'sys_language_uid',
                        $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'l10n_parent',
                        $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                    )
                )
                ->executeQuery()
                ->fetchAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            $eventRecord = null;
        }

        return $eventRecord;
    }

    protected function getQueryBuilderForTable(string $table, bool $allowHidden = false): QueryBuilder
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        if (!$allowHidden) {
            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(HiddenRestriction::class));
        }

        return $queryBuilder;
    }

    private function getUniqueIdForNewRecords(): string
    {
        return str_replace('.', '', uniqid('NEW', true));
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
