<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$imports = [
    '@jweiland/events2/remaining-letters.js' => 'EXT:events2/Resources/Public/JavaScript/remaining-letters.js',
    '@jweiland/events2/rich-text-editor.js' => 'EXT:events2/Resources/Public/JavaScript/rich-text-editor.js',
];

// The CKEditor 5 ES modules of EXT:rte_ckeditor. They are mapped here, as the
// import map of EXT:rte_ckeditor depends on the TYPO3 backend.
if (ExtensionManagementUtility::isLoaded('rte_ckeditor')) {
    $ckeditorModules = require ExtensionManagementUtility::extPath('rte_ckeditor') . 'Configuration/JavaScriptModules.php';
    foreach ($ckeditorModules['imports'] ?? [] as $specifier => $path) {
        if (is_string($path) && str_starts_with($specifier, '@ckeditor/')) {
            $imports[$specifier] = $path;
        }
    }
}

return [
    'dependencies' => [],
    'imports' => $imports,
];
