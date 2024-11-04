<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Configuration;

/**
 * This configuration contains all needed data to start an event2 record import
 */
class ImportConfiguration
{
    private array $payload;

    private int $storagePid;

    public function __construct(int $storagePid, array $payload)
    {
        $this->storagePid = $storagePid;
        $this->payload = $payload;
    }

    public function getStoragePid(): int
    {
        return $this->storagePid;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}
