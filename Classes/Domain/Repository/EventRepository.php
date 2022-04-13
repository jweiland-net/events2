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
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/*
 * Repository to get and find event records
 */
class EventRepository extends AbstractRepository implements HiddenRepositoryInterface
{
    public const TABLE = 'tx_events2_domain_model_event';

    protected $defaultOrderings = [
        'eventBegin' => QueryInterface::ORDER_ASCENDING,
    ];

    protected array $settings = [];

    protected UserRepository $userRepository;

    protected ExceptionRepository $exceptionRepository;

    public function injectUserRepository(UserRepository $userRepository): void
    {
        $this->userRepository = $userRepository;
    }

    public function injectExceptionRepository(ExceptionRepository $exceptionRepository): void
    {
        $this->exceptionRepository = $exceptionRepository;
    }

    /**
     * @param mixed $value
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

    public function findMyEvents(): QueryResultInterface
    {
        $organizer = (int)$this->userRepository->getFieldFromUser('tx_events2_organizer');
        $query = $this->createQuery();

        return $query->matching($query->equals('organizers.uid', $organizer))->execute();
    }

    /**
     * Nearly the same as "findByUid", but this method is used by PageTitleProvider,
     * which is out of Extbase context. So we are using a plain Doctrine Query here.
     */
    public function getRecord(
        int $uid,
        array $select = ['*'],
        bool $includeHidden = false,
        bool $includeExceptions = true,
        bool $doOverlay = true
    ): array {
        $eventRecord = $this->getRecordByUid(
            self::TABLE,
            'e',
            $uid,
            $select,
            $includeHidden,
            $doOverlay
        );

        $eventRecord['days'] = [];
        $eventRecord['exceptions'] = [];
        if ($includeExceptions) {
            $eventRecord['exceptions'] = $this->exceptionRepository->getAllByEventRecord($eventRecord);
        }

        return $eventRecord;
    }
}
