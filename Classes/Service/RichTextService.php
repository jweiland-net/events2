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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Prepares the HTML of the rich text editor of the frontend form for the
 * database: It is sanitized with a strict allowlist and then transformed
 * with the RTE processing configuration of the backend (RteHtmlParser), so
 * the stored value is the same as if the event was saved in backend.
 *
 * Without JavaScript the textarea is submitted as plain text. Values without
 * any tag are converted like the plain text description.
 */
readonly class RichTextService
{
    private const TABLE = 'tx_events2_domain_model_event';

    private const FIELD = 'detail_information';

    public function __construct(
        private RichTextSanitizer $richTextSanitizer,
        private RteContentConverter $rteContentConverter,
        private Richtext $richtext,
        private ConnectionPool $connectionPool,
    ) {}

    /**
     * @param int $eventUid UID of the event to update. 0 for new events.
     * @param int $pid Page UID of a new event to read the RTE configuration from
     */
    public function prepareForPersistence(string $value, int $eventUid, int $pid = 0): string
    {
        if (trim(strip_tags($value)) === trim($value)) {
            return $this->rteContentConverter->convertPlainTextToHtml($value);
        }

        $storedRecord = $this->getStoredRecord($eventUid);
        $html = $this->richTextSanitizer->sanitize(
            $value,
            $this->richTextSanitizer->getT3Hrefs((string)($storedRecord[self::FIELD] ?? '')),
        );

        return $this->transformForPersistence($html, (int)($storedRecord['pid'] ?? $pid), (string)($storedRecord['event_type'] ?? ''));
    }

    /**
     * Same transformation as DataHandler does for fields with enableRichtext
     */
    private function transformForPersistence(string $html, int $pid, string $recordType): string
    {
        $configuration = $this->richtext->getConfiguration(
            self::TABLE,
            self::FIELD,
            $pid,
            $recordType,
            $GLOBALS['TCA'][self::TABLE]['columns'][self::FIELD]['config'] ?? [],
        );

        // RteHtmlParser is stateful
        return GeneralUtility::makeInstance(RteHtmlParser::class)
            ->transformTextForPersistence($html, $configuration['proc.'] ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    private function getStoredRecord(int $eventUid): array
    {
        if ($eventUid <= 0) {
            return [];
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());

        $record = $queryBuilder
            ->select('pid', 'event_type', self::FIELD)
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($eventUid, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchAssociative();

        return is_array($record) ? $record : [];
    }
}
