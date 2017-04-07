<?php

namespace JWeiland\Events2\Domain\Repository;

/*
 * This file is part of the TYPO3 CMS project.
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
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class OrganizerRepository extends Repository
{
    /**
     * @var array
     */
    protected $defaultOrderings = array(
        'organizer' => QueryInterface::ORDER_ASCENDING,
    );
    
    /**
     * Get aöllowed organizers for filter
     *
     * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function getOrganizersForFilter()
    {
        $query = $this->createQuery();
        return $query->matching($query->equals('hide_in_filter', 0))->execute();
    }
}
