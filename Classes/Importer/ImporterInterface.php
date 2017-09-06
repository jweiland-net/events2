<?php

namespace JWeiland\Events2\Importer;

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
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Class Import
 *
 * @package JWeiland\Events2\Importer
 */
interface ImporterInterface
{
    /**
     * Initialize the object
     *
     * @return void
     */
    public function initialize();

    /**
     * Check, if File is valid for this importer
     *
     * @param File $file
     *
     * @return bool
     */
    public function isValid(File $file);

    /**
     * Import XML file
     *
     * @param File $file
     * @param AbstractTask $task
     *
     * @return bool
     */
    public function import(File $file, AbstractTask $task);
}
