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
use JWeiland\Events2\Utility\DateTimeUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\Generic\Session;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/*
 * Repository to get and find event records
 */
class EventRepository extends Repository
{
    /**
     * @var array
     */
    protected $defaultOrderings = [
        'eventBegin' => QueryInterface::ORDER_ASCENDING,
    ];

    /**
     * @var DateTimeUtility
     */
    protected $dateTimeUtility;

    /**
     * @var DataMapper
     */
    protected $dataMapper;

    /**
     * @var Session
     */
    protected $persistenceSession;

    /**
     * @var ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * @var array
     */
    protected $settings = [];

    public function __construct(ObjectManagerInterface $objectManager, DateTimeUtility $dateTimeUtility)
    {
        parent::__construct($objectManager);
        $this->dateTimeUtility = $dateTimeUtility;
    }

    public function injectDataMapper(DataMapper $dataMapper)
    {
        $this->dataMapper = $dataMapper;
    }

    public function injectPersistenceSession(Session $persistenceSession)
    {
        $this->persistenceSession = $persistenceSession;
    }

    public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    public function setSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Find event by a given property value whether it is hidden or not.
     *
     * @param mixed $value
     * @param string $property
     * @return Event|null
     */
    public function findHiddenEntry($value, string $property = 'uid'): ?Event
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setIgnoreEnableFields(true);
        $query->getQuerySettings()->setEnableFieldsToBeIgnored(['disabled']);
        $query->getQuerySettings()->setRespectStoragePage(false);

        /** @var Event $event */
        $event = $query->matching($query->equals($property, $value))->execute()->getFirst();
        return $event;
    }

    public function findMyEvents(): QueryResultInterface
    {
        $userRepository = GeneralUtility::makeInstance(UserRepository::class);
        $organizer = (int)$userRepository->getFieldFromUser('tx_events2_organizer');
        $query = $this->createQuery();

        return $query->matching($query->equals('organizer.uid', $organizer))->execute();
    }

    /**
     * Nearly the same as "findByUid", but this method was used by PageTitleProvider
     * which is out of Extbase context. So we are using a plain Doctrine Query here.
     *
     * @param int $uid
     * @return array
     */
    public function getEventRecord(int $uid): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_event');
        $event = $queryBuilder
            ->select('uid', 'title')
            ->from('tx_events2_domain_model_event')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter((int)$uid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();

        if (empty($event)) {
            $event = [];
        }
        return $event;
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
