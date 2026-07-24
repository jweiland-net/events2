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

/**
 * Reduce the number of categories to given root category declared in extension configuration
 */
readonly class ModifyRootUidOfTreeSelectElements implements FormDataProviderInterface
{
    private const CATEGORY_FIELDS = [
        'settings.categories',
        'settings.mainCategories',
    ];

    public function __construct(
        private ExtConf $extConf,
    ) {}

    /**
     * Set rootUid of the tree select elements of FlexForms to root category declared in EM
     */
    public function addData(array $result): array
    {
        foreach (self::CATEGORY_FIELDS as $categoryField) {
            if (
                // check global structure
                isset(
                    $result['flexParentDatabaseRow']['CType'],
                    $result['processedTca']['columns'][$categoryField]['config']['type'],
                    $result['processedTca']['columns'][$categoryField]['config']['renderMode'],
                )

                // check, if we have TCA-type "select" defined, and it is configured as "tree"
                && $result['processedTca']['columns'][$categoryField]['config']['type'] === 'select'
                && $result['processedTca']['columns'][$categoryField]['config']['renderMode'] === 'tree'

                // check if a FlexForm was rendered
                && $result['tableName'] === 'tt_content'
                && \str_starts_with($result['flexParentDatabaseRow']['CType'], 'events2')
            ) {
                $result['processedTca']['columns'][$categoryField]['config']['treeConfig']['startingPoints']
                    = $this->extConf->getRootUid();
            }
        }

        return $result;
    }
}
