<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Helper;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

/**
 * Helper to add where clause for translations and workspaces to QueryBuilder
 */
class OverlayHelper
{
    protected Context $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function addWhereForOverlay(
        QueryBuilder $queryBuilder,
        string $tableName,
        string $tableAlias,
        bool $useLangStrict = false
    ): void {
        $this->addWhereForWorkspaces($queryBuilder, $tableName, $tableAlias);
        $this->addWhereForTranslation($queryBuilder, $tableName, $tableAlias, $useLangStrict);
    }

    protected function addWhereForWorkspaces(QueryBuilder $queryBuilder, string $tableName, string $tableAlias): void
    {
        if ($GLOBALS['TCA'][$tableName]['ctrl']['versioningWS']) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq($tableAlias . '.t3ver_oid', 0)
            );
        }
    }

    /**
     * Add WHERE clause to get records in requested language.
     *
     * @param string $tableName tablename to read the localization columns from TCA ctrl
     * @param string $tableAlias the table alias as configured in $queryBuilder->from(table, tableAlias)
     * @param bool $useLangStrict in case of a search like "letter=b" it does not make sense to search for "b" (bicycle) in default language, do an overlay and show "Fahrrad" in frontend. Activate for search queries. Else false.
     * @param int $overrideLanguageUid if $useStrictLang is activated you can override the languageId from LanguageAspect
     */
    protected function addWhereForTranslation(
        QueryBuilder $queryBuilder,
        string $tableName,
        string $tableAlias,
        bool $useLangStrict = false,
        int $overrideLanguageUid = -1
    ): void {
        // Column: sys_language_uid
        $languageField = $GLOBALS['TCA'][$tableName]['ctrl']['languageField'];
        // Column: l10n_parent
        $transOrigPointerField = $GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'] ?? '';

        if (!$useLangStrict && $this->getLanguageAspect()->doOverlays()) {
            // Get record in default language (0)
            // sys_language_uid IN (0, -1) and l10n_parent = 0
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in(
                    $tableAlias . '.' . $languageField,
                    [0, -1]
                ),
                $queryBuilder->expr()->eq(
                    $tableAlias . '.' . $transOrigPointerField,
                    0
                )
            );
        } elseif ($useLangStrict && $overrideLanguageUid >= 0) {
            // Strict mode for f.e. backend (TCEFORM)
            // sys_language_uid = {requestedLanguageUid}
            // Without check against all languages
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in(
                    $tableAlias . '.' . $languageField,
                    [$overrideLanguageUid]
                )
            );
        } else {
            // strict mode
            // sys_language_uid = {requestedLanguageUid} || all languages
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in(
                    $tableAlias . '.' . $languageField,
                    [$this->getLanguageAspect()->getContentId(), -1]
                )
            );
        }
    }

    protected function getLanguageAspect(): LanguageAspect
    {
        return $this->context->getAspect('language');
    }
}
