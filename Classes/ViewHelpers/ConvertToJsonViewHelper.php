<?php
namespace JWeiland\Events2\ViewHelpers;

/**
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
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class ConvertToJsonViewHelper
 *
 * @category ViewHelpers
 * @author   Stefan Froemken <projects@jweiland.net>
 * @license  http://www.gnu.org/licenses/gpl.html GNU General Public License
 * @link     https://github.com/jweiland-net/events2
 */
class ConvertToJsonViewHelper extends AbstractViewHelper
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
     * implements a ViewHelper to convert an array into JSON format
     *
     * @return array
     */
    public function render()
    {
        $value = $this->renderChildren();
        if (empty($value)) {
            $json = '{}';
        } else {
            $json = json_encode($value);
        }

        return htmlspecialchars($json);
    }
}
