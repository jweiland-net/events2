<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Repository;

/*
 * We need the link repository in AbstractController to remove video link objects, if link itself is empty.
 */
class LinkRepository extends Repository
{
}
