<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Exporter;

readonly class ExporterConfiguration
{
    public function __construct(
        private string $url,
        private string $secret,
        private array $storagePages,
        private array $categoryUids,
    ) {}

    public function getUrl(): string
    {
        return trim($this->url);
    }

    public function getSecret(): string
    {
        return trim($this->secret);
    }

    public function getStoragePages(): array
    {
        return $this->storagePages;
    }

    public function getCategoryUids(): array
    {
        return $this->categoryUids;
    }
}
