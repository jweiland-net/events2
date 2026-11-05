<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Sanitizer;

use JWeiland\Events2\Sanitizer\Visitor\RichTextVisitor;
use TYPO3\HtmlSanitizer\Behavior;
use TYPO3\HtmlSanitizer\Behavior\Attr;
use TYPO3\HtmlSanitizer\Behavior\ClosureAttrValue;
use TYPO3\HtmlSanitizer\Behavior\Tag;
use TYPO3\HtmlSanitizer\Visitor\CommonVisitor;

/**
 * Sanitizes the HTML of the rich text editor in the frontend form. Never
 * trust the client: a POST request can bypass the editor.
 *
 * Allowed: p, br, strong, em, h2, h3, ul, ol, li and a with href (http,
 * https, mailto, tel) and target="_blank" (rel="noopener noreferrer" is
 * added). Links with t3:// are allowed only, if exactly the same href is
 * part of $allowedT3Hrefs, which are the t3:// links of the stored record.
 * So links added by editors in backend are kept, but no new or modified
 * t3:// links can be injected.
 *
 * Everything else is removed. The text of unknown elements is kept, the
 * content of script, style, iframe and similar elements is removed. Unknown
 * block elements like <div> or <td> become paragraphs and inline content
 * outside of blocks is wrapped into <p>, as RteHtmlParser would strip its
 * formatting otherwise.
 */
readonly class RichTextSanitizer
{
    private const ALLOWED_SCHEMES_PATTERN = '#^(?:https?://|mailto:|tel:)\S+$#i';

    /**
     * @param string[] $allowedT3Hrefs
     */
    public function sanitize(string $html, array $allowedT3Hrefs = []): string
    {
        $behavior = (new Behavior())
            ->withName('events2-rich-text')
            ->withTags(...$this->getAllowedTags($allowedT3Hrefs));

        $sanitizer = new RichTextHtmlSanitizer($behavior, new RichTextVisitor(), new CommonVisitor($behavior));

        return $sanitizer->sanitize(mb_scrub($html, 'UTF-8'));
    }

    /**
     * Returns the t3:// hrefs of all links in the given HTML (entity decoded)
     *
     * @return string[]
     */
    public function getT3Hrefs(string $html): array
    {
        preg_match_all('/<a\s[^>]*?href\s*=\s*"([^"]*)"/i', $html, $matches);

        $hrefs = array_map(
            static fn(string $href): string => html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            $matches[1],
        );

        return array_values(array_unique(array_filter(
            $hrefs,
            static fn(string $href): bool => str_starts_with(strtolower($href), 't3://'),
        )));
    }

    /**
     * @param string[] $allowedT3Hrefs
     * @return Tag[]
     */
    private function getAllowedTags(array $allowedT3Hrefs): array
    {
        $tags = [new Tag('br'), $this->getLinkTag($allowedT3Hrefs), new Tag(RichTextVisitor::UNWRAP_TAG, Tag::ALLOW_CHILDREN)];
        foreach (array_diff(RichTextVisitor::ALLOWED_TAGS, ['br', 'a']) as $tagName) {
            $tags[] = new Tag($tagName, Tag::ALLOW_CHILDREN);
        }

        return $tags;
    }

    /**
     * @param string[] $allowedT3Hrefs
     */
    private function getLinkTag(array $allowedT3Hrefs): Tag
    {
        $isAllowedHref = static fn(string $href): bool => str_starts_with(strtolower(trim($href)), 't3://')
            ? in_array($href, $allowedT3Hrefs, true)
            : preg_match(self::ALLOWED_SCHEMES_PATTERN, trim($href)) === 1;

        return (new Tag('a', Tag::ALLOW_CHILDREN))->addAttrs(
            (new Attr('href'))->addValues(new ClosureAttrValue($isAllowedHref)),
            (new Attr('target'))->addValues(new ClosureAttrValue(static fn(string $value): bool => $value === '_blank')),
            new Attr('rel'),
        );
    }
}
