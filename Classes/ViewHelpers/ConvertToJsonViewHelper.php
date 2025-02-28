<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * This VH is designed to convert PoiCollection record into JSON.
 * But, you can use it for all other arrays, too.
 */
final class ConvertToJsonViewHelper extends AbstractViewHelper
{
    /**
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Implements a ViewHelper to convert an array into JSON format
     */
    public function render(): string
    {
        $value = $this->renderChildren();
        $json = empty($value) ? '{}' : json_encode($value, JSON_THROW_ON_ERROR);

        return htmlspecialchars($json);
    }
}
