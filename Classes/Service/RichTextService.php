<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Service;

use JWeiland\Events2\Converter\RteContentConverter;
use JWeiland\Events2\Sanitizer\RichTextSanitizer;
use TYPO3\CMS\Core\Configuration\Richtext;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Html\RteHtmlParser;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Prepares the HTML of a rich text element of a frontend form for the
 * database column it is mapped to: It is sanitized with a strict allowlist
 * and then transformed with the RTE processing configuration of the backend
 * (RteHtmlParser), so the stored value is the same as if the record was
 * saved in backend. The column should be configured with "enableRichtext"
 * in TCA.
 *
 * t3:// links are kept only, if they are part of the value currently stored
 * in the same column of the same record.
 *
 * Without JavaScript the textarea is submitted as plain text. Values without
 * any tag are converted like the plain text description.
 */
readonly class RichTextService
{
    public function __construct(
        private RichTextSanitizer $richTextSanitizer,
        private RteContentConverter $rteContentConverter,
        private Richtext $richtext,
        private ConnectionPool $connectionPool,
        private TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    /**
     * @param int $uid UID of the record to update. 0 for new records.
     * @param int $pid Page UID of a new record to read the RTE configuration from
     */
    public function prepareForPersistence(string $value, string $table, string $column, int $uid, int $pid = 0): string
    {
        if (trim(strip_tags($value)) === trim($value)) {
            return $this->rteContentConverter->convertPlainTextToHtml($value);
        }

        $storedRecord = $this->getStoredRecord($table, $column, $uid);
        $html = $this->richTextSanitizer->sanitize(
            $value,
            $this->richTextSanitizer->getT3Hrefs((string)($storedRecord[$column] ?? '')),
            $table,
            $column,
        );

        return $this->transformForPersistence(
            $html,
            $table,
            $column,
            (int)($storedRecord['pid'] ?? $pid),
            (string)($storedRecord['recordType'] ?? ''),
        );
    }

    /**
     * Same transformation as DataHandler does for fields with enableRichtext
     */
    private function transformForPersistence(string $html, string $table, string $column, int $pid, string $recordType): string
    {
        $configuration = $this->richtext->getConfiguration(
            $table,
            $column,
            $pid,
            $recordType,
            $this->getField($table, $column)?->getConfiguration() ?? [],
        );

        // RteHtmlParser is stateful
        return GeneralUtility::makeInstance(RteHtmlParser::class)
            ->transformTextForPersistence($html, $configuration['proc.'] ?? []);
    }

    /**
     * Returns "pid", the stored value of $column and the "recordType" of the record
     *
     * @return array<string, mixed>
     */
    private function getStoredRecord(string $table, string $column, int $uid): array
    {
        if ($uid <= 0 || $this->getField($table, $column) === null) {
            return [];
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());

        $typeField = $this->tcaSchemaFactory->get($table)->getSubSchemaDivisorField()?->getName() ?? '';
        $record = $queryBuilder
            ->select(...array_filter(['pid', $column, $typeField]))
            ->from($table)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative();

        if (!is_array($record)) {
            return [];
        }

        $record['recordType'] = $record[$typeField] ?? '';

        return $record;
    }

    private function getField(string $table, string $column): ?FieldTypeInterface
    {
        if (!$this->tcaSchemaFactory->has($table)) {
            return null;
        }

        $schema = $this->tcaSchemaFactory->get($table);

        return $schema->hasField($column) ? $schema->getField($column) : null;
    }
}
