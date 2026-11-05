<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Sanitizer;

use JWeiland\Events2\Event\ModifyRichTextSanitizerConfigurationEvent;

/**
 * The normalized tag configuration of RichTextSanitizer:
 *
 * - All tag names are lower case
 * - <p> is always allowed, as inline content is wrapped into paragraphs
 * - Elements with active or embedded content are always removed
 * - Removed tags take precedence over allowed tags
 * - Tags are mapped only, if they are not allowed and the target is allowed
 */
final readonly class RichTextSanitizerConfiguration
{
    /**
     * These elements can not be allowed by configuration
     */
    private const ALWAYS_REMOVED_TAGS = [
        'applet', 'base', 'embed', 'frame', 'frameset', 'iframe', 'link', 'math', 'meta', 'noscript',
        'object', 'script', 'style', 'svg', 'template',
    ];

    /**
     * Allowed block elements, which are not valid outside of their parent element
     */
    private const CHILD_BLOCK_TAGS = ['caption', 'dd', 'dt', 'li', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr'];

    /**
     * @param string[] $allowedTags
     * @param array<string, string> $mappedTags
     * @param string[] $removedTags
     * @param string[] $blockTags
     */
    private function __construct(
        public array $allowedTags,
        public array $mappedTags,
        public array $removedTags,
        public array $blockTags,
    ) {}

    public static function fromEvent(ModifyRichTextSanitizerConfigurationEvent $event): self
    {
        $removedTags = array_values(array_unique([...self::normalize($event->getRemovedTags()), ...self::ALWAYS_REMOVED_TAGS]));
        $allowedTags = array_values(array_diff(
            array_unique(['p', ...self::normalize($event->getAllowedTags())]),
            $removedTags,
        ));

        $mappedTags = [];
        foreach ($event->getMappedTags() as $tagName => $targetTagName) {
            $tagName = strtolower(trim((string)$tagName));
            $targetTagName = is_string($targetTagName) ? strtolower(trim($targetTagName)) : '';
            if (in_array($targetTagName, $allowedTags, true)
                && !in_array($tagName, $allowedTags, true)
                && !in_array($tagName, $removedTags, true)
            ) {
                $mappedTags[$tagName] = $targetTagName;
            }
        }

        return new self($allowedTags, $mappedTags, $removedTags, self::normalize($event->getBlockTags()));
    }

    /**
     * Allowed block elements, which are valid on top level. Inline content
     * between them is wrapped into <p>.
     *
     * @return string[]
     */
    public function getTopLevelBlockTags(): array
    {
        return array_values(array_diff(array_intersect($this->allowedTags, $this->blockTags), self::CHILD_BLOCK_TAGS));
    }

    /**
     * @param mixed[] $tagNames
     * @return string[]
     */
    private static function normalize(array $tagNames): array
    {
        $tagNames = array_map(static fn(mixed $tagName): string => is_string($tagName) ? strtolower(trim($tagName)) : '', $tagNames);

        return array_values(array_unique(array_filter(
            $tagNames,
            static fn(string $tagName): bool => preg_match('/^[a-z][a-z0-9]*$/', $tagName) === 1,
        )));
    }
}
