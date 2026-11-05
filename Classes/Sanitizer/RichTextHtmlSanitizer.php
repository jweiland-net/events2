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
use TYPO3\HtmlSanitizer\InitiatorInterface;
use TYPO3\HtmlSanitizer\Sanitizer;

/**
 * Finishes the DOM after all visitors have sanitized it:
 *
 * - Elements renamed to RichTextVisitor::UNWRAP_TAG are replaced by their
 *   (already sanitized) children.
 * - Inline content outside of blocks is wrapped into <p>, like CKEditor does.
 *   RteHtmlParser would strip <strong>, <em> and <a> of content outside of
 *   blocks (only "allowTagsOutside" are kept) before wrapping it into <p>.
 */
final class RichTextHtmlSanitizer extends Sanitizer
{
    private const BLOCK_TAGS = ['p', 'h2', 'h3', 'ul', 'ol'];

    protected function handle(\DOMNode $domNode, ?InitiatorInterface $initiator = null): \DOMNode
    {
        $domNode = parent::handle($domNode, $initiator);
        $this->unwrapElements($domNode);
        $this->wrapInlineNodes($domNode);

        return $domNode;
    }

    private function unwrapElements(\DOMNode $domNode): void
    {
        foreach (iterator_to_array($domNode->childNodes) as $childNode) {
            $this->unwrapElements($childNode);
            if (!$childNode instanceof \DOMElement || $childNode->nodeName !== RichTextVisitor::UNWRAP_TAG) {
                continue;
            }

            while ($childNode->firstChild !== null) {
                $domNode->insertBefore($childNode->firstChild, $childNode);
            }
            $domNode->removeChild($childNode);
        }
    }

    /**
     * Wraps each run of top level inline nodes into <p>. Runs without text are removed.
     */
    private function wrapInlineNodes(\DOMNode $domNode): void
    {
        $inlineNodes = [];
        foreach (iterator_to_array($domNode->childNodes) as $childNode) {
            if (!$this->isBlock($childNode)) {
                $inlineNodes[] = $childNode;
                continue;
            }
            $this->wrapIntoParagraph($domNode, $inlineNodes, $childNode);
            $inlineNodes = [];
        }

        $this->wrapIntoParagraph($domNode, $inlineNodes, null);
    }

    /**
     * @param \DOMNode[] $inlineNodes
     */
    private function wrapIntoParagraph(\DOMNode $domNode, array $inlineNodes, ?\DOMNode $nextBlock): void
    {
        $text = implode('', array_map(static fn(\DOMNode $node): string => (string)$node->textContent, $inlineNodes));
        $paragraph = $domNode->ownerDocument?->createElement('p');
        if (trim($text) === '' || $paragraph === null) {
            // Whitespace and line breaks between blocks are no content
            array_map(static fn(\DOMNode $node): \DOMNode => $domNode->removeChild($node), $inlineNodes);
            return;
        }

        $domNode->insertBefore($paragraph, $nextBlock);
        foreach ($inlineNodes as $inlineNode) {
            $paragraph->appendChild($inlineNode);
        }
    }

    private function isBlock(\DOMNode $domNode): bool
    {
        return $domNode instanceof \DOMElement && in_array($domNode->nodeName, self::BLOCK_TAGS, true);
    }
}
