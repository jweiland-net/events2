<?php

namespace JWeiland\Events2\ViewHelpers\Widget;

/*
 * This file is part of the TYPO3 CMS project.
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
use TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetViewHelper;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class PaginateViewHelper extends AbstractWidgetViewHelper
{
    /**
     * @var \JWeiland\Events2\ViewHelpers\Widget\Controller\PaginateController
     * @inject
     */
    protected $controller;

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\QueryResultInterface $objects
     * @param string                                              $as
     * @param int                                                 $maxRecords
     * @param array                                               $configuration
     *
     * @return string
     */
    public function render(\TYPO3\CMS\Extbase\Persistence\QueryResultInterface $objects, $as, $maxRecords = 0, array $configuration = array('itemsPerPage' => 10, 'insertAbove' => false, 'insertBelow' => true, 'maximumNumberOfLinks' => 99))
    {
        return $this->initiateSubRequest();
    }
}
