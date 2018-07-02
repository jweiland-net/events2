<?php

namespace JWeiland\Events2\ViewHelpers;

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
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class CreateYoutubeUriViewHelper extends AbstractViewHelper
{
    /**
     * extract the youtube ID from link and return the embedded youtube url.
     *
     * @param string $link
     *
     * @return string Embedded Youtube URI
     */
    public function render($link)
    {
        if (preg_match('~^(|http:|https:)//(|www.)youtu(\.be|be)(.*?)(v=|embed/|)([a-zA-Z0-9_-]+)$~i', $link, $matches)) {
            $url = '//www.youtube.com/embed/' . $matches[6];
        } else {
            $url = '//www.youtube.com/embed/' . $link;
        }

        return $url;
    }
}
