<?php
declare(strict_types = 1);
namespace JWeiland\Events2\Domain\Model;

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

/**
 * We extend the original category class here, because we have some additional methods to find
 * categories in CategoryRepository.
 *
 * It will be used by Ajax calls and while building the search form.
 */
class Category extends \TYPO3\CMS\Extbase\Domain\Model\Category
{
}
