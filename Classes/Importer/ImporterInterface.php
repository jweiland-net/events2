<?php

declare(strict_types = 1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace JWeiland\Events2\Importer;

use TYPO3\CMS\Core\Resource\FileInterface;

/*
 * An Interface as base for all event importer classes
 */
interface ImporterInterface
{
    /**
     * Check, if File is valid for this importer
     *
     * @param FileInterface $file
     * @return bool
     */
    public function isValid(FileInterface $file): bool;

    /**
     * Import XML file
     *
     * @return bool
     */
    public function import(): bool;
}
