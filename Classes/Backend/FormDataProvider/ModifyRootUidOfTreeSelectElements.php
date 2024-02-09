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
use TYPO3\CMS\Core\Information\Typo3Version;

/**
 * Reduce amount of categories to given root category declared in extension configuration
 */
class ModifyRootUidOfTreeSelectElements implements FormDataProviderInterface
{
    public function __construct(
        private readonly ExtConf $extConf,
        private readonly Typo3Version $typo3Version
    ) {}

    /**
     * Set rootUid of tree select elements of FlexForms to root category declared in EM
     */
    public function addData(array $result): array
    {
        foreach (['settings.categories', 'settings.mainCategories'] as $categoryField) {
            if (
                // check global structure
                isset(
                    $result['flexParentDatabaseRow']['list_type'],
                    $result['processedTca']['columns'][$categoryField]['config']['type'],
                    $result['processedTca']['columns'][$categoryField]['config']['renderMode']
                )

                // check, if we have TCA-type "select" defined, and it is configured as "tree"
                && $result['processedTca']['columns'][$categoryField]['config']['type'] === 'select'
                && $result['processedTca']['columns'][$categoryField]['config']['renderMode'] === 'tree'

                // check if a FlexForm was rendered
                && $result['tableName'] === 'tt_content'
                && \str_starts_with($result['flexParentDatabaseRow']['list_type'], 'events2')
            ) {
                if (version_compare($this->typo3Version->getBranch(), '11.4', '<')) {
                    $result['processedTca']['columns'][$categoryField]['config']['treeConfig']['rootUid']
                        = $this->extConf->getRootUid();
                } else {
                    $result['processedTca']['columns'][$categoryField]['config']['treeConfig']['startingPoints']
                        = $this->extConf->getRootUid();
                }
            }
        }

        return $result;
    }
}
