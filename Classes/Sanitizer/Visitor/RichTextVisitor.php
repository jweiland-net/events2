<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Sanitizer\Visitor;

use TYPO3\HtmlSanitizer\Visitor\AbstractVisitor;

/**
 * Runs before the CommonVisitor of typo3/html-sanitizer, which removes
 * unknown elements together with their content. This visitor:
 *
 * - removes comments and elements with active or embedded content including their content
 * - maps <b> to <strong>, <i> to <em> and headings except h2/h3 to <p>
 * - renames every other unknown element to UNWRAP_TAG, so its text is kept.
 *   RichTextSanitizer removes the UNWRAP_TAG tags after serialization.
 * - unwraps links without href and normalizes "target" and "rel" of links
 */
final class RichTextVisitor extends AbstractVisitor
{
    public const UNWRAP_TAG = 'events2-unwrap';

    public const ALLOWED_TAGS = ['p', 'br', 'strong', 'em', 'h2', 'h3', 'ul', 'ol', 'li', 'a'];

    private const MAPPED_TAGS = [
        'b' => 'strong',
        'i' => 'em',
        'h1' => 'p',
        'h4' => 'p',
        'h5' => 'p',
        'h6' => 'p',
    ];

    private const REMOVED_TAGS = [
        'applet', 'area', 'audio', 'base', 'button', 'canvas', 'embed', 'form', 'frame', 'frameset',
        'head', 'iframe', 'img', 'input', 'link', 'map', 'math', 'meta', 'noscript', 'object', 'option',
        'picture', 'script', 'select', 'source', 'style', 'svg', 'template', 'textarea', 'title',
        'track', 'video',
    ];

    public function enterNode(\DOMNode $domNode): ?\DOMNode
    {
        // Comments, CDATA sections and processing instructions are removed
        if (!$domNode instanceof \DOMElement) {
            return $domNode instanceof \DOMText ? $domNode : null;
        }

        $tagName = strtolower($domNode->nodeName);
        if (in_array($tagName, self::REMOVED_TAGS, true)) {
            return null;
        }

        if (isset(self::MAPPED_TAGS[$tagName])) {
            return $this->renameElement($domNode, self::MAPPED_TAGS[$tagName]);
        }

        return in_array($tagName, self::ALLOWED_TAGS, true)
            ? $domNode
            : $this->renameElement($domNode, self::UNWRAP_TAG);
    }

    public function leaveNode(\DOMNode $domNode): ?\DOMNode
    {
        if (!$domNode instanceof \DOMElement || strtolower($domNode->nodeName) !== 'a') {
            return $domNode;
        }

        // Invalid hrefs have been removed by the CommonVisitor. Keep the link text only.
        if (!$domNode->hasAttribute('href')) {
            return $this->renameElement($domNode, self::UNWRAP_TAG);
        }

        $domNode->setAttribute('href', trim($domNode->getAttribute('href')));
        if ($domNode->getAttribute('target') === '_blank') {
            $domNode->setAttribute('rel', 'noopener noreferrer');
        } else {
            $domNode->removeAttribute('target');
            $domNode->removeAttribute('rel');
        }

        return $domNode;
    }

    /**
     * Creates a new element without any attribute and moves all children into it
     */
    private function renameElement(\DOMElement $domElement, string $tagName): ?\DOMElement
    {
        $newElement = $domElement->ownerDocument?->createElement($tagName);
        if (!$newElement instanceof \DOMElement) {
            return null;
        }

        while ($domElement->firstChild !== null) {
            $newElement->appendChild($domElement->firstChild);
        }

        return $newElement;
    }
}
