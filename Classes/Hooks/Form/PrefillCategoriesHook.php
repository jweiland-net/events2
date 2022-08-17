<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Hooks\Form;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;

/*
 * Prefill EXT:form element of type checkboxes with categories from database
 */
class PrefillCategoriesHook
{

    /**
     * @var PageRepository
     */
    protected $pageRepository;

    /**
     * @var array
     */
    protected $settings = [];

    public function __construct(PageRepository $pageRepository, ConfigurationManagerInterface $configurationManager)
    {
        $this->pageRepository = $pageRepository;

        $this->settings = $configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS,
            'Events2',
            'Management'
        );
    }

    /**
     * This method will be called by Form Framework.
     * It was checked by method_exists() before
     */
    public function initializeFormElement(RenderableInterface $formElement): void
    {
        if (!$formElement instanceof FormElementInterface) {
            return;
        }

        if ($formElement->getUniqueIdentifier() === 'newEvent-categories') {
            $formElement->setProperty(
                'options',
                $this->getCategories()
            );
        }
    }

    protected function getCategories(): array
    {
        $categories = [];
        $statement = $this->getStatementForCategoriesInDefaultLanguage();
        while ($sysCategoryInDefaultLanguage = $statement->fetch()) {
            $sysCategoryTranslated = $this->pageRepository->getLanguageOverlay(
                'sys_category',
                $sysCategoryInDefaultLanguage
            );

            if ($sysCategoryTranslated === null) {
                continue;
            }

            $categories[$sysCategoryInDefaultLanguage['uid']] = $sysCategoryInDefaultLanguage['title'];
        }

        sort($categories, SORT_LOCALE_STRING);

        return $categories;
    }

    protected function getStatementForCategoriesInDefaultLanguage(): Statement
    {
        $queryBuilder = $this->getQueryBuilderForCategories();

        return $queryBuilder
            ->select('*')
            ->from('sys_category')
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter(
                        $this->getSelectableCategoriesForNewEvents(),
                        Connection::PARAM_INT_ARRAY
                    )
                ),
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA']['sys_category']['ctrl']['languageField'],
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                )
            )
            ->execute();
    }

    protected function getSelectableCategoriesForNewEvents(): array
    {
        $selectableCategoriesForNewEvents = $this->settings['selectableCategoriesForNewEvents'] === ''
            ? '0'
            : $this->settings['selectableCategoriesForNewEvents'];

        return GeneralUtility::intExplode(',', $selectableCategoriesForNewEvents, true);
    }

    protected function getQueryBuilderForCategories(): QueryBuilder
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_categories');
        $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));

        return $queryBuilder;
    }

    protected function getPageRepository(): PageRepository
    {
        return GeneralUtility::makeInstance(PageRepository::class);
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
