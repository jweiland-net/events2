<?php

declare(strict_types = 1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Model;

/*
 * We extend the original category class here, because we have some additional methods to find
 * categories in CategoryRepository.
 *
 * It will be used by Ajax calls and while building the search form.
 */
class Category extends \TYPO3\CMS\Extbase\Domain\Model\Category
{
}
