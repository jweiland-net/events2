<?php
declare(strict_types = 1);
namespace JWeiland\Events2\Service;

/*
 * This file is part of the events2 project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * A Service to merge the TypoScript Settings (Framework) into the merged Settings (inkl. FlexForm),
 * if these are empty or 0
 */
class TypoScriptService
{
    /**
     * @param array $mergedFlexFormSettings
     * @param array $typoScriptSettings
     */
    public function override(array &$mergedFlexFormSettings, array $typoScriptSettings)
    {
        foreach ($typoScriptSettings as $property => $value) {
            if (isset($typoScriptSettings[$property]) && is_array($typoScriptSettings[$property])) {
                if (is_array($typoScriptSettings[$property])) {
                    $this->override($mergedFlexFormSettings[$property], $typoScriptSettings[$property]);
                }
            } elseif (
                $mergedFlexFormSettings[$property] === '0' ||
                (
                    is_string($mergedFlexFormSettings[$property]) &&
                    strlen($mergedFlexFormSettings[$property]) === 0
                )
            ) {
                $mergedFlexFormSettings[$property] = $value;
            }
        }
    }
}
