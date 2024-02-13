<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Traits;

use JWeiland\Events2\Domain\Repository\CategoryRepository;

/**
 * Trait to inject CategoryRepository. Mostly used in controllers.
 */
trait InjectCategoryRepositoryTrait
{
    protected CategoryRepository $categoryRepository;

    public function injectCategoryRepository(CategoryRepository $categoryRepository): void
    {
        $this->categoryRepository = $categoryRepository;
    }
}
