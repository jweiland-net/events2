<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * This ViewHelper explodes a string by a specified glue and limits the output to a specified number of elements.
 * It is useful for formatting settings or any other string that needs to be split and limited.
 * Example usage:
 *
 * <code>
 * {namespace e2=JWeiland\Events2\ViewHelpers}
 * <e2:cropString content="{contentSeperatedByCommas}" glue="," limit="5" />
 * </code>
 *
 * In this example, the `cropString` ViewHelper will take the `contentSeperatedByCommas` string, split it by commas,
 * and return the first 5 elements as a comma-separated string. If there are more than 5 elements,
 * it will append "..." to the end of the string.
 */
class CropStringViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    public function initializeArguments(): void
    {
        $this->registerArgument('content', 'string', 'String to be formatted by limit if set');
        $this->registerArgument(
            'glue',
            'string',
            'String "glue" that separates values. If you need a constant (like PHP_EOL)',
            false,
            ','
        );
        $this->registerArgument(
            'limit',
            'int',
            'If the limit is higher than the given content (array of categories) count it will just return the comma ' .
            'seperated values. If the limit is set and positive, only the first few values are combined into a new ' .
            'comma-separated string, for example "75,44,62,...". If the limit parameter is negative, ' .
            'only the last few values are combined into a new comma-separated string, for example "...,1,56,7". ' .
            'If the limit parameter is zero, it is treated as 1, so only ONE element is shown, for example  "75".',
            false,
            10
        );
    }

    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): string {
        $content = $arguments['content'];
        $glue = $arguments['glue'];
        $limit = $arguments['limit'] ?? 10;

        $explodedStringArray = explode($glue, (string)$content);

        // return the imploded content with glue if limit is higher than the given content count
        if ($limit > count($explodedStringArray)) {
            return implode($glue, $explodedStringArray);
        }

        // return the imploded content with glue if limit is set and positive with limit  given or default 10 and  post fix the content with "..."
        if ($limit > 0) {
            $output = array_slice($explodedStringArray, 0, $limit);
            return implode($glue, $output) . '...';
        }

        // return the imploded content with glue if limit is set and negative with limit  given or default 10 and  pre fix the content with "..."
        if ($limit < 0) {
            $output = array_slice($explodedStringArray, $limit);
            return '...' . implode($glue, $output);
        }

        // return the first element of the exploded content if limit is set to 0
        if ($limit === 0) {
            return $explodedStringArray[0];
        }
    }
}
