<?php

namespace JWeiland\Events2\Hooks;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ModifyTcaOfCategoryTrees
{
    /**
     * @var \JWeiland\Events2\Configuration\ExtConf
     */
    protected $extConf;

    /**
     * inject extConf
     * It will not be auto-loaded as in extbase, but it is good to have this method for testing.
     *
     * @param \JWeiland\Events2\Configuration\ExtConf $extConf
     */
    public function injectExtConf(\JWeiland\Events2\Configuration\ExtConf $extConf)
    {
        $this->extConf = $extConf;
    }

    /**
     * constructor of this class.
     */
    public function __construct()
    {
        $this->extConf = GeneralUtility::makeInstance('JWeiland\\Events2\\Configuration\\ExtConf');
    }

    /**
     * change rootUid to a value defined in EXT_CONF.
     *
     * @param string $table
     * @param string $field
     * @param array  $row
     * @param array  $PA
     */
    public function getSingleField_beforeRender($table, $field, $row, &$PA)
    {
        // check if a FlexForm was rendered
        if ($table === 'tt_content' && $field === 'pi_flexform' && GeneralUtility::isFirstPartOfStr($row['list_type'], 'events2')) {
            // check, if TCA configuration exists
            if (isset($PA['fieldConf']) && is_array($PA['fieldConf']) && isset($PA['fieldConf']['config']) && is_array($PA['fieldConf']['config'])) {
                // check, if we have TCA-type "select" defined and it is configured as "tree"
                if (isset($PA['fieldConf']['config']['type']) && $PA['fieldConf']['config']['type'] === 'select' && isset($PA['fieldConf']['config']['renderMode']) && $PA['fieldConf']['config']['renderMode'] === 'tree') {
                    $PA['fieldConf']['config']['treeConfig']['rootUid'] = (int)$this->extConf->getRootUid();
                }
            }
        }
    }
}
