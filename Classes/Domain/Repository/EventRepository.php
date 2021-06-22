<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Repository;

use JWeiland\Events2\Domain\Model\Event;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Extbase\Service\EnvironmentService;

/*
 * Repository to get and find event records
 */
class EventRepository extends Repository implements HiddenRepositoryInterface
{
    /**
     * @var array
     */
    protected $defaultOrderings = [
        'eventBegin' => QueryInterface::ORDER_ASCENDING,
    ];

    /**
     * @var array
     */
    protected $settings = [];

    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * @var EnvironmentService
     */
    protected $environmentService;

    public function __construct(
        ObjectManagerInterface $objectManager,
        UserRepository $userRepository,
        EnvironmentService $environmentService
    ) {
        parent::__construct($objectManager);

        $this->userRepository = $userRepository;
        $this->environmentService = $environmentService;
    }

    /**
     * @param mixed $value
     * @param string $property
     * @return AbstractDomainObject|Event|null
     */
    public function findHiddenObject($value, string $property = 'uid'): ?AbstractDomainObject
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setIgnoreEnableFields(true);
        $query->getQuerySettings()->setEnableFieldsToBeIgnored(['disabled']);
        $query->getQuerySettings()->setRespectStoragePage(false);

        $firstObject = $query->matching($query->equals($property, $value))->execute()->getFirst();
        if ($firstObject instanceof AbstractDomainObject) {
            return $firstObject;
        }

        return null;
    }

    /**
     * A very fast method to just get the event record as array by its event UID.
     * Currently used to re-generate day records at CLI and task.
     *
     * @param int $eventUid
     * @param bool $ignoreEnableFields If true hidden/start/end will not be included.
     * @return array
     */
    public function getEventRecord(int $eventUid, bool $ignoreEnableFields = false): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_event');
        if ($this->environmentService->isEnvironmentInFrontendMode()) {
            $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
        }

        if ($ignoreEnableFields) {
            $queryBuilder->getRestrictions()->removeByType(StartTimeRestriction::class);
            $queryBuilder->getRestrictions()->removeByType(EndTimeRestriction::class);
            $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        }

        $eventRecord = $queryBuilder
            ->select('*')
            ->from('tx_events2_domain_model_event')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($eventUid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();

        return $eventRecord ?: [];
    }

    /**
     * A very fast method to delete all related days of a given event UID.
     * Currently used to re-generate day records at CLI and task.
     *
     * @param int $eventUid
     * @return void
     */
    public function deleteRelatedDayRecords(int $eventUid): void
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_day');
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $queryBuilder
            ->delete('tx_events2_domain_model_day')
            ->where(
                $queryBuilder->expr()->eq(
                    'event',
                    $queryBuilder->createNamedParameter($eventUid)
                )
            )
            ->execute();
    }

    public function findMyEvents(): QueryResultInterface
    {
        $organizer = (int)$this->userRepository->getFieldFromUser('tx_events2_organizer');
        $query = $this->createQuery();

        return $query->matching($query->equals('organizers.uid', $organizer))->execute();
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
