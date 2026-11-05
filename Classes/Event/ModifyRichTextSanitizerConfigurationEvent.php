<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Event;

/**
 * Use this event to modify the elements, which are allowed in the HTML of a
 * rich text element of the frontend form. The defaults are defined as
 * constants in RichTextVisitor.
 *
 * Only tag names are configurable. Allowed elements never get attributes,
 * except "a", which keeps a checked "href" and target="_blank".
 * Removed tags take precedence over allowed tags. Mapped tags whose target
 * is not allowed are ignored.
 */
final class ModifyRichTextSanitizerConfigurationEvent
{
    /**
     * @param string[] $allowedTags Elements, which are kept
     * @param array<string, string> $mappedTags Elements, which are renamed (e.g. "b" => "strong")
     * @param string[] $removedTags Elements, which are removed including their content
     * @param string[] $blockTags Elements, which start a new block. Unknown ones become a paragraph.
     * @param string $table Table of the column the HTML is stored in. Empty, if unknown.
     * @param string $column Column the HTML is stored in. Empty, if unknown.
     */
    public function __construct(
        private array $allowedTags,
        private array $mappedTags,
        private array $removedTags,
        private array $blockTags,
        private readonly string $table = '',
        private readonly string $column = '',
    ) {}

    /**
     * @return string[]
     */
    public function getAllowedTags(): array
    {
        return $this->allowedTags;
    }

    /**
     * @param string[] $allowedTags
     */
    public function setAllowedTags(array $allowedTags): void
    {
        $this->allowedTags = $allowedTags;
    }

    /**
     * @return array<string, string>
     */
    public function getMappedTags(): array
    {
        return $this->mappedTags;
    }

    /**
     * @param array<string, string> $mappedTags
     */
    public function setMappedTags(array $mappedTags): void
    {
        $this->mappedTags = $mappedTags;
    }

    /**
     * @return string[]
     */
    public function getRemovedTags(): array
    {
        return $this->removedTags;
    }

    /**
     * @param string[] $removedTags
     */
    public function setRemovedTags(array $removedTags): void
    {
        $this->removedTags = $removedTags;
    }

    /**
     * @return string[]
     */
    public function getBlockTags(): array
    {
        return $this->blockTags;
    }

    /**
     * @param string[] $blockTags
     */
    public function setBlockTags(array $blockTags): void
    {
        $this->blockTags = $blockTags;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getColumn(): string
    {
        return $this->column;
    }
}
