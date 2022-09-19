<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Hooks\Form;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement;
use TYPO3\CMS\Form\Domain\Model\Renderable\RootRenderableInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;

/*
 * Prefill EXT:form element of type Events2Location with location label from DB
 */
class PrefillLocationHook
{
    /**
     * @var PageRepository
     */
    protected $pageRepository;

    public function __construct(PageRepository $pageRepository, ConfigurationManagerInterface $configurationManager)
    {
        $this->pageRepository = $pageRepository;
    }

    /**
     * This method will be called by Form Framework.
     * It was checked by method_exists() before
     */
    public function beforeRendering(FormRuntime $formRuntime, RootRenderableInterface $formElement): void
    {
        if (!$formElement instanceof GenericFormElement) {
            return;
        }

        if ($formElement->getIdentifier() === 'event-location') {
            $locationUid = (int)($formRuntime->getElementValue('event-location') ?? 0);
            $location = $this->getLocationByUid($locationUid);
            if ($location !== '') {
                $formElement->setProperty('valueForNonConnectedElement', $location);
            }
        }
    }

    protected function getLocationByUid(int $locationUid): string
    {
        $queryBuilder = $this->getQueryBuilderForTable('tx_events2_domain_model_location');

        $location = $queryBuilder
            ->select('location')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter(
                        $locationUid,
                        \PDO::PARAM_INT
                    )
                )
            )
            ->execute()
            ->fetch();

        return is_array($location) && isset($location['location']) ? $location['location'] : '';
    }

    protected function getQueryBuilderForTable(string $table): QueryBuilder
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($table);
        $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
        $queryBuilder->from($table);

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
