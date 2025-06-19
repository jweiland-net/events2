<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Repository;

use JWeiland\Events2\Traits\InjectUserRepositoryTrait;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Repository to get and find event records
 */
class EventRepository extends Repository implements HiddenRepositoryInterface
{
    use InjectUserRepositoryTrait;

    public const TABLE = 'tx_events2_domain_model_event';

    protected $defaultOrderings = [
        'eventBegin' => QueryInterface::ORDER_ASCENDING,
    ];

    protected array $settings = [];

    public function findHiddenObject(mixed $value, string $property = 'uid'): ?AbstractDomainObject
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
}
