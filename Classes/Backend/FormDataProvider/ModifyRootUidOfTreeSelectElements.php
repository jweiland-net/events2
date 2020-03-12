<?php
declare(strict_types = 1);
namespace JWeiland\Events2\Backend\FormDataProvider;

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

use JWeiland\Events2\Configuration\ExtConf;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Reduce amount of categories to given root category declared in extension configuration
 */
class ModifyRootUidOfTreeSelectElements implements FormDataProviderInterface
{
    /**
     * Set rootUid of tree select elements of FlexForms to root category declared in EM
     *
     * @param array $result Initialized result array
     * @return array
     */
    public function addData(array $result): array
    {
        foreach (['settings.categories', 'settings.mainCategories'] as $categoryField) {
            if (
                // check if a FlexForm was rendered
                $result['tableName'] === 'tt_content'
                && isset($result['flexParentDatabaseRow'])
                && is_array($result['flexParentDatabaseRow'])
                && GeneralUtility::isFirstPartOfStr($result['flexParentDatabaseRow']['list_type'], 'events2')

                // check, if TCA configuration exists
                && isset($result['processedTca']['columns'][$categoryField])
                && is_array($result['processedTca']['columns'][$categoryField])
                && isset($result['processedTca']['columns'][$categoryField]['config'])
                && is_array($result['processedTca']['columns'][$categoryField]['config'])

                // check, if we have TCA-type "select" defined and it is configured as "tree"
                && isset($result['processedTca']['columns'][$categoryField]['config']['type'])
                && $result['processedTca']['columns'][$categoryField]['config']['type'] === 'select'
                && isset($result['processedTca']['columns'][$categoryField]['config']['renderMode'])
                && $result['processedTca']['columns'][$categoryField]['config']['renderMode'] === 'tree'
            ) {
                $extConf = GeneralUtility::makeInstance(ExtConf::class);
                $result['processedTca']['columns'][$categoryField]['config']['treeConfig']['rootUid'] = (int)$extConf->getRootUid();
            }
        }

        return $result;
    }
}
