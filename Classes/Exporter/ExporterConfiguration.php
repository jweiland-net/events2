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
    private string $url;

    private string $secret;

    private array $storagePages;

    public function __construct(string $url, string $secret, array $storagePages)
    {
        $this->url = trim($url);
        $this->secret = trim($secret);
        $this->storagePages = $storagePages;
    }

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
