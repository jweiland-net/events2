<?php
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::makeCategorizable(
    'events2',
    'tx_events2_domain_model_event',
    'categories',
    array(
        'fieldConfiguration' => array(
            'foreign_table_where' => ' AND sys_category.sys_language_uid IN (-1, 0) ORDER BY sys_category.title ASC',
        ),
    )
);
