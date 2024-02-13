<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Importer;

use TYPO3\CMS\Core\Resource\FileInterface;

/**
 * An Interface as base for all event importer classes
 */
interface ImporterInterface
{
    /**
     * Set storage pid
     */
    public function setStoragePid(int $storagePid): void;

    /**
     * Set file to import
     */
    public function setFile(FileInterface $file): void;

    /**
     * Check, if File is valid for this importer
     */
    public function checkFile(): bool;

    /**
     * Import XML file
     */
    public function import(): bool;
}
