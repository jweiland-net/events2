<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Repository;

use JWeiland\Events2\Domain\Model\Organizer;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/*
 * The organizer repository is used to sort the organizers in our create-new-form. Further it will be used in
 * our event importer
 */
class OrganizerRepository extends Repository
{
    protected $defaultOrderings = [
        'organizer' => QueryInterface::ORDER_ASCENDING,
    ];

    /**
     * Get allowed organizers for filter
     *
     * @return QueryResultInterface|Organizer[]
     */
    public function getOrganizersForFilter(): QueryResultInterface
    {
        $query = $this->createQuery();
        return $query->matching($query->equals('hide_in_filter', 0))->execute();
    }
}
