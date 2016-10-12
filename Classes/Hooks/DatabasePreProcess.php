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
use TYPO3\CMS\Core\Database\PreProcessQueryHookInterface;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class DatabasePreProcess implements PreProcessQueryHookInterface
{
    /**
     * Pre-processor for the INSERTquery method.
     *
     * @param string $table Database table name
     * @param array $fieldsValues Field values as key => value pairs
     * @param string/array $noQuoteFields List/array of keys NOT to quote
     * @param \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject
     *
     * @return void
     */
    public function INSERTquery_preProcessAction(&$table, array &$fieldsValues, &$noQuoteFields, \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject)
    {
        if ($table === 'tx_events2_domain_model_event') {
            // col 'day' is only a helper for events and is not existing in DB
            // I need 'day' in TCA for mapping in EventRepository and PaginateWidget
            // There is no hook in extbase, to prevent specified properties from saving
            // I don't like this approach, but I don't see any other possibility currently
            if (array_key_exists('day', $fieldsValues)) {
                unset($fieldsValues['day']);
            }
        }
    }
    
    /**
     * Pre-processor for the SELECTquery method.
     *
     * @param string $select_fields Fields to be selected
     * @param string $from_table Table to select data from
     * @param string $where_clause Where clause
     * @param string $groupBy Group by statement
     * @param string $orderBy Order by statement
     * @param int $limit Database return limit
     * @param \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject
     *
     * @return void
     */
    public function SELECTquery_preProcessAction(&$select_fields, &$from_table, &$where_clause, &$groupBy, &$orderBy, &$limit, \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject)
    {
        
    }
    
    /**
     * Pre-processor for the INSERTmultipleRows method.
     * BEWARE: When using DBAL, this hook will not be called at all. Instead,
     * INSERTquery_preProcessAction() will be invoked for each row.
     *
     * @param string $table Database table name
     * @param array $fields Field names
     * @param array $rows Table rows
     * @param string/array $noQuoteFields List/array of keys NOT to quote
     * @param \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject
     * @return void
     */
    public function INSERTmultipleRows_preProcessAction(&$table, array &$fields, array &$rows, &$noQuoteFields, \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject)
    {
        
    }
    
    /**
     * Pre-processor for the UPDATEquery method.
     *
     * @param string $table Database table name
     * @param string $where WHERE clause
     * @param array $fieldsValues Field values as key => value pairs
     * @param string/array $noQuoteFields List/array of keys NOT to quote
     * @param \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject
     * @return void
     */
    public function UPDATEquery_preProcessAction(&$table, &$where, array &$fieldsValues, &$noQuoteFields, \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject)
    {
        
    }
    
    /**
     * Pre-processor for the DELETEquery method.
     *
     * @param string $table Database table name
     * @param string $where WHERE clause
     * @param \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject
     * @return void
     */
    public function DELETEquery_preProcessAction(&$table, &$where, \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject)
    {
        
    }
    
    /**
     * Pre-processor for the TRUNCATEquery method.
     *
     * @param string $table Database table name
     * @param \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject
     * @return void
     */
    public function TRUNCATEquery_preProcessAction(&$table, \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject)
    {
        
    }
}
