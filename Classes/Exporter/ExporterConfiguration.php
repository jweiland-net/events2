<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Exporter;

class ExporterConfiguration
{
    public function __construct(
        private readonly string $url,
        private readonly string $secret,
        private readonly array $storagePages,
    ) {}

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function getStoragePages(): array
    {
        return $this->storagePages;
    }
}
