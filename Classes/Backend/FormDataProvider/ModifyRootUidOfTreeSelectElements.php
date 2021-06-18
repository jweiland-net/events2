<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Backend\FormDataProvider;

use JWeiland\Events2\Configuration\ExtConf;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * Reduce amount of categories to given root category declared in extension configuration
 */
class ModifyRootUidOfTreeSelectElements implements FormDataProviderInterface
{
    /**
     * @var ExtConf
     */
    protected $extConf;

    public function __construct(ExtConf $extConf)
    {
        $this->extConf = $extConf;
    }

    /**
     * Set rootUid of tree select elements of FlexForms to root category declared in EM
     *
     * @param array $result Initialized result array
     * @return array Do not add as strict type because of Interface
     */
    public function addData(array $result): array
    {
        foreach (['settings.categories', 'settings.mainCategories'] as $categoryField) {
            if (
                // check global structure
                isset(
                    $result['flexParentDatabaseRow'],
                    $result['processedTca']['columns'][$categoryField]['config']['type'],
                    $result['processedTca']['columns'][$categoryField]['config']['renderMode']
                )

                // check, if TCA configuration exists
                && is_array($result['processedTca']['columns'][$categoryField])
                && is_array($result['processedTca']['columns'][$categoryField]['config'])

                // check, if we have TCA-type "select" defined and it is configured as "tree"
                && $result['processedTca']['columns'][$categoryField]['config']['type'] === 'select'
                && $result['processedTca']['columns'][$categoryField]['config']['renderMode'] === 'tree'

                // check if a FlexForm was rendered
                && $result['tableName'] === 'tt_content'
                && is_array($result['flexParentDatabaseRow'])
                && GeneralUtility::isFirstPartOfStr($result['flexParentDatabaseRow']['list_type'], 'events2')
            ) {
                $result['processedTca']['columns'][$categoryField]['config']['treeConfig']['rootUid']
                    = $this->extConf->getRootUid();
            }
        }

        return $result;
    }
}
