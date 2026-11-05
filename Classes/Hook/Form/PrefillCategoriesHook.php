<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Hook\Form;

use Doctrine\DBAL\ArrayParameterType;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Form\Domain\Model\FormElements\AbstractFormElement;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;

/**
 * Prefill EXT:form element of type checkboxes with categories from database.
 *
 * Only direct children of "settings.rootCategory" are offered. If
 * "settings.new.selectableCategoriesForNewEvents" is missing or empty, all
 * direct children of "settings.rootCategory" are offered. Without both
 * settings, no categories are offered at all, so the category tree of the
 * whole installation will never be exposed in frontend.
 */
readonly class PrefillCategoriesHook
{
    private const FORM_ELEMENT_IDENTIFIER = 'newEvent-categories';

    public function __construct(
        private PageRepository $pageRepository,
        private ConfigurationManagerInterface $configurationManager,
        private ConnectionPool $connectionPool,
    ) {}

    /**
     * This method will be called by Form Framework.
     * It was checked by method_exists() before
     */
    public function initializeFormElement(RenderableInterface $formElement): void
    {
        if (!$formElement instanceof AbstractFormElement
            || $formElement->getUniqueIdentifier() !== self::FORM_ELEMENT_IDENTIFIER
        ) {
            return;
        }

        $formElement->setProperty('options', $this->getCategories());
    }

    /**
     * @return array<int, string>
     */
    private function getCategories(): array
    {
        $settings = $this->getSettings();
        $rootCategory = $this->getRootCategory($settings);
        $selectableCategories = $this->getSelectableCategoriesForNewEvents($settings);
        if ($rootCategory === 0 && $selectableCategories === []) {
            return [];
        }

        $categories = [];
        foreach ($this->getCategoriesInDefaultLanguage($rootCategory, $selectableCategories) as $sysCategory) {
            $translatedSysCategory = $this->pageRepository->getLanguageOverlay('sys_category', $sysCategory);
            if ($translatedSysCategory === null) {
                continue;
            }

            $categories[(int)$sysCategory['uid']] = (string)$translatedSysCategory['title'];
        }

        asort($categories, SORT_LOCALE_STRING);

        return $categories;
    }

    /**
     * @param int[] $selectableCategories
     * @return array<int, array<string, mixed>>
     */
    private function getCategoriesInDefaultLanguage(int $rootCategory, array $selectableCategories): array
    {
        $queryBuilder = $this->getQueryBuilderForSysCategory();
        $queryBuilder
            ->select('*')
            ->from('sys_category')
            ->where(
                $queryBuilder->expr()->eq(
                    'parent',
                    $queryBuilder->createNamedParameter($rootCategory, Connection::PARAM_INT),
                ),
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA']['sys_category']['ctrl']['languageField'],
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT),
                ),
            );

        if ($selectableCategories !== []) {
            $queryBuilder->andWhere($queryBuilder->expr()->in(
                'uid',
                $queryBuilder->createNamedParameter($selectableCategories, ArrayParameterType::INTEGER),
            ));
        }

        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    /**
     * @return array<string, mixed>
     */
    private function getSettings(): array
    {
        return $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS,
            'Events2',
            'Management',
        );
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function getRootCategory(array $settings): int
    {
        $rootCategory = $settings['rootCategory'] ?? 0;

        return is_numeric($rootCategory) ? max(0, (int)$rootCategory) : 0;
    }

    /**
     * The value is a comma separated list of category UIDs (string) or a
     * single category UID (int). A missing or empty value results in an
     * empty array.
     *
     * @param array<string, mixed> $settings
     * @return int[]
     */
    private function getSelectableCategoriesForNewEvents(array $settings): array
    {
        $selectableCategories = $settings['new']['selectableCategoriesForNewEvents'] ?? '';
        if (!is_string($selectableCategories) && !is_int($selectableCategories)) {
            return [];
        }

        return array_values(array_filter(
            GeneralUtility::intExplode(',', (string)$selectableCategories, true),
            static fn(int $categoryUid): bool => $categoryUid > 0,
        ));
    }

    private function getQueryBuilderForSysCategory(): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_category');
        $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));

        return $queryBuilder;
    }
}
