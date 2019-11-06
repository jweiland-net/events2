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
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * The location repository is used to sort the locations in our create-new-form. Further it will be used in
 * our event importer
 */
class LocationRepository extends Repository
{
    /**
     * @var array
     */
    protected $defaultOrderings = [
        'location' => QueryInterface::ORDER_ASCENDING,
    ];
}
