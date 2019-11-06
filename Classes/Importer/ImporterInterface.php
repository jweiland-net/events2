<?php
declare(strict_types = 1);
namespace JWeiland\Events2\Importer;

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
use TYPO3\CMS\Core\Resource\FileInterface;

/**
 * Class Import
 *
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
