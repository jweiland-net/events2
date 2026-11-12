<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\ViewHelpers;

use JWeiland\Events2\Converter\RteContentConverter;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * There is no RTE for teaser/detailInformation in frontend, so any markup in there either comes
 * from a backend editor using the RTE, or from SaveEventFinisher::convertPlainTextToHtml(), which
 * only ever produces <p> and <br> tags. This ViewHelper turns </p> and <br> back into real line
 * breaks, strips every other tag along with its markup (keeping its text content), decodes HTML
 * entities and escapes the result. Chain it with f:format.nl2br to get visible line breaks again.
 */
final class ConvertHtmlToPlainTextViewHelper extends AbstractViewHelper
{
    /**
     * No output escaping, as we build our own htmlspecialchars-escaped text.
     */
    protected $escapeOutput = false;

    /**
     * The value must reach render() unescaped, else strip_tags() of the converter has
     * nothing left to strip.
     */
    protected $escapeChildren = false;

    public function __construct(
        private readonly RteContentConverter $rteContentConverter,
    ) {}

    public function initializeArguments(): void
    {
        $this->registerArgument('value', 'string', 'The HTML formatted value to convert to plain text', false, null);
    }

    public function getContentArgumentName(): string
    {
        return 'value';
    }

    public function render(): string
    {
        $value = $this->renderChildren();
        if (!is_string($value)) {
            return '';
        }

        return htmlspecialchars(
            $this->rteContentConverter->convertHtmlToPlainText($value),
            ENT_QUOTES | ENT_HTML5,
        );
    }
}
