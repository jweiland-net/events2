<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\ViewHelpers;

use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * SettingsFormatViewHelper ViewHelper
 *
 * Explodes a string by $glue.
 */
class SettingsFormatViewHelper extends AbstractViewHelper
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
            'If limit is set and positive, the returned array will contain a maximum of limit elements ' .
            'with the last element containing the rest of string. If the limit parameter is negative, all ' .
            'components except the last-limit are returned. If the limit parameter is zero, then this is treated as 1.',
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
        $limit = $arguments['limit'] ?? MathUtility::forceIntegerInRange($arguments['limit'], 1);
        $explodedStringArray = explode($glue, (string)$content);
        $output = array_slice($explodedStringArray, 0, $limit);

        return implode(', ', $output) . '", ...';
    }
}
