<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Helper;

use TYPO3\CMS\Core\DataHandling\Model\RecordState;
use TYPO3\CMS\Core\DataHandling\Model\RecordStateFactory;
use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Generates the path segment (slug) of an event record outside the
 * DataHandler (frontend form, XML import, upgrade wizard) the same way the
 * DataHandler does: the TCA configuration of "path_segment" is used for
 * SlugHelper::generate(), followed by the uniqueness checks configured in
 * "eval". Modify the generated slug with "postModifiers" in the TCA
 * "generatorOptions".
 *
 * @see \TYPO3\CMS\Core\DataHandling\DataHandler::checkValueForSlug()
 */
readonly class PathSegmentHelper
{
    private const TABLE = 'tx_events2_domain_model_event';

    private const SLUG_COLUMN = 'path_segment';

    /**
     * Same order as in DataHandler::checkValueForSlug()
     */
    private const UNIQUE_EVALUATIONS = [
        'unique' => 'buildSlugForUniqueInTable',
        'uniqueInSite' => 'buildSlugForUniqueInSite',
        'uniqueInPid' => 'buildSlugForUniqueInPid',
    ];

    public function __construct(
        private TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    /**
     * A record without "uid" is handled as a new, not yet stored record.
     * A stored record (with "uid") will not collide with its own slug.
     *
     * @param array<string, mixed> $record
     * @throws SiteNotFoundException
     */
    public function generatePathSegment(array $record): string
    {
        $configuration = $this->tcaSchemaFactory
            ->get(self::TABLE)
            ->getField(self::SLUG_COLUMN)
            ->getConfiguration();
        $slugHelper = GeneralUtility::makeInstance(SlugHelper::class, self::TABLE, self::SLUG_COLUMN, $configuration);
        $pid = (int)($record['pid'] ?? 0);
        $slug = $slugHelper->generate($record, $pid);
        $recordState = $this->getRecordState($record, $pid);
        $evaluations = GeneralUtility::trimExplode(',', (string)($configuration['eval'] ?? ''), true);

        foreach (self::UNIQUE_EVALUATIONS as $evaluation => $method) {
            if (in_array($evaluation, $evaluations, true)) {
                $slug = $slugHelper->{$method}($slug, $recordState);
            }
        }

        return $slug;
    }

    /**
     * @param array<string, mixed> $record
     */
    private function getRecordState(array $record, int $pid): RecordState
    {
        // Like DataHandler: a non-numeric identifier ("NEW...") excludes no record from the uniqueness check
        $uid = (int)($record['uid'] ?? 0);

        return RecordStateFactory::forName(self::TABLE)->fromArray($record, $pid, $uid > 0 ? $uid : 'NEW');
    }
}
