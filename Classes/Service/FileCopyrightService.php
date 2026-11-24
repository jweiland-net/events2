<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Service;

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

/**
 * Read and store the copyright of a file. The column "copyright" of
 * sys_file_metadata is provided by EXT:filemetadata, which is optional.
 */
readonly class FileCopyrightService
{
    private const TABLE = 'sys_file_metadata';

    private const COLUMN = 'copyright';

    public function __construct(
        private TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    /**
     * Returns false, if a copyright is given, but can not be stored
     */
    public function storeCopyright(File $file, mixed $copyright): bool
    {
        $copyright = is_string($copyright) ? trim($copyright) : '';
        if ($copyright === '') {
            return true;
        }

        if (!$this->hasCopyrightColumn()) {
            return false;
        }

        $file->getMetaData()->add([self::COLUMN => $copyright])->save();

        return true;
    }

    public function getCopyright(File $file): string
    {
        if (!$this->hasCopyrightColumn()) {
            return '';
        }

        $copyright = $file->getMetaData()->offsetExists(self::COLUMN)
            ? $file->getMetaData()->offsetGet(self::COLUMN)
            : '';

        return is_string($copyright) ? trim($copyright) : '';
    }

    private function hasCopyrightColumn(): bool
    {
        return $this->tcaSchemaFactory->has(self::TABLE)
            && $this->tcaSchemaFactory->get(self::TABLE)->hasField(self::COLUMN);
    }
}
