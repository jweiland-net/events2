<?php

namespace JWeiland\Events2\ViewHelpers;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Stefan Froemken <projects@jweiland.net>, jweiland.net
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ConvertFalToUrlViewHelper extends AbstractViewHelper
{
    /**
     * inject ResourceFactory.
     *
     * @var \TYPO3\CMS\Core\Resource\ResourceFactory
     * @inject
     */
    protected $resourceFactory;

    /**
     * implements a vievHelper to convert seconds since 0:00 to a readable format.
     *
     * @param string $fal A value like file:34657
     *
     * @return string Downloadlink to file
     */
    public function render($fal)
    {
        // $fal is a value from a typolink field in BE and it can contain additional params like _blank, typeNum and some more.
        // With this line I extract the first part
        $linkParts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(' ', $fal);
        $image = $this->resourceFactory->retrieveFileOrFolderObject($linkParts[0]);
        $path = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL');

        return $path.$image->getPublicUrl();
    }
}
