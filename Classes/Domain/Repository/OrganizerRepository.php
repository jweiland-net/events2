<?php
declare(strict_types = 1);
namespace JWeiland\Events2\Domain\Repository;

/*
 * This file is part of the events2 project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use JWeiland\Events2\Domain\Model\Organizer;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * The organizer repository is used to sort the organizers in our create-new-form. Further it will be used in
 * our event importer
 */
class OrganizerRepository extends Repository
{
    /**
     * @var array
     */
    protected $defaultOrderings = [
        'organizer' => QueryInterface::ORDER_ASCENDING,
    ];

    /**
     * Get allowed organizers for filter
     *
     * @return QueryResultInterface|Organizer[]
     */
    public function getOrganizersForFilter()
    {
        $query = $this->createQuery();
        return $query->matching($query->equals('hide_in_filter', 0))->execute();
    }
}
