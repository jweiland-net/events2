<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Configuration;

use TYPO3\CMS\Reactions\Model\ReactionInstruction;

/**
 * This configuration contains all needed data to start an event2 record import
 */
class ImportConfiguration
{
    private array $payload;

    private int $storagePid;

    private string $storageFolder;

    public function __construct(ReactionInstruction $reactionInstruction)
    {
        $reactionRecord = $reactionInstruction->toArray();
        $this->payload = $reactionRecord['payload'] ?? [];
        $this->storagePid = (int)($reactionRecord['storage_pid'] ?? 0);
        $this->storageFolder = $reactionRecord['storage_folder'] ?? '';
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getStoragePid(): int
    {
        return $this->storagePid;
    }

    public function getStorageFolder(): string
    {
        return $this->storageFolder;
    }
}
