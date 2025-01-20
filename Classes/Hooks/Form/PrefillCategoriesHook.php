<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Hooks\Form;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Result;
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
 * Prefill EXT:form element of type checkboxes with categories from database
 */
class PrefillCategoriesHook
{
    protected array $settings = [];

    public function __construct(
        protected readonly PageRepository $pageRepository,
        protected readonly ConfigurationManagerInterface $configurationManager,
    ) {
        $this->settings = $configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS,
            'Events2',
            'Management',
        );
    }

    /**
     * This method will be called by Form Framework.
     * It was checked by method_exists() before
     */
    public function initializeFormElement(RenderableInterface $formElement): void
    {
        if (!$formElement instanceof AbstractFormElement) {
            return;
        }

        if ($formElement->getUniqueIdentifier() === 'newEvent-categories') {
            $formElement->setProperty(
                'options',
                $this->getCategories(),
            );
        }
    }

    protected function getCategories(): array
    {
        $categories = [];
        $queryResult = $this->getQueryResultForCategoriesInDefaultLanguage();
        while ($sysCategoryInDefaultLanguage = $queryResult->fetchAssociative()) {
            $sysCategoryTranslated = $this->pageRepository->getLanguageOverlay(
                'sys_category',
                $sysCategoryInDefaultLanguage,
            );

            if ($sysCategoryTranslated === null) {
                continue;
            }

            $categories[$sysCategoryInDefaultLanguage['uid']] = $sysCategoryInDefaultLanguage['title'];
        }

        asort($categories, SORT_LOCALE_STRING);

        return $categories;
    }

    protected function getQueryResultForCategoriesInDefaultLanguage(): Result
    {
        $queryBuilder = $this->getQueryBuilderForTable('sys_category');

        return $queryBuilder
            ->select('*')
            ->from('sys_category')
            ->where(
                $queryBuilder->expr()->eq(
                    'parent',
                    $queryBuilder->createNamedParameter(
                        $this->settings['rootCategory'] ?? 0,
                        Connection::PARAM_INT,
                    ),
                ),
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter(
                        $this->getSelectableCategoriesForNewEvents(),
                        ArrayParameterType::INTEGER,
                    ),
                ),
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA']['sys_category']['ctrl']['languageField'],
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT),
                ),
            )
            ->executeQuery();
    }

    protected function getSelectableCategoriesForNewEvents(): array
    {
        if (isset($this->settings['selectableCategoriesForNewEvents'])) {
            trigger_error(
                'settings.selectableCategoriesForNewEvents is deprecated. Please of settings.new.selectableCategoriesForNewEvents instead.',
                E_USER_DEPRECATED,
            );
            $selectableCategories = $this->settings['new']['selectableCategoriesForNewEvents'] ?? $this->settings['selectableCategoriesForNewEvents'];
        } else {
            $selectableCategories = $this->settings['new']['selectableCategoriesForNewEvents'];
        }

        $selectableCategoriesForNewEvents = $selectableCategories === ''
            ? '0'
            : $selectableCategories;

        return GeneralUtility::intExplode(',', $selectableCategoriesForNewEvents, true);
    }

    protected function getQueryBuilderForTable(string $table): QueryBuilder
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($table);
        $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));

        return $queryBuilder;
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
