<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Tca\CountryItems;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$extConf = GeneralUtility::makeInstance(ExtConf::class);

$tx_events2_domain_model_event = [
    'ctrl' => [
        'title' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_location',
        'label' => 'location',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'default_sortby' => 'ORDER BY location',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'searchFields' => 'location',
        'typeicon_classes' => [
            'default' => 'ext-events2-record-location',
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => '--palette--;;languageHidden, l10n_diffsource, location,
            --palette--;;streetHouseNumber,
            --palette--;;zipCity,
            country, link,
            --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.access,
            --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.access;access',
        ],
    ],
    'palettes' => [
        'languageHidden' => ['showitem' => 'sys_language_uid, l10n_parent, hidden'],
        'streetHouseNumber' => ['showitem' => 'street, house_number'],
        'zipCity' => ['showitem' => 'zip, city'],
        'access' => [
            'showitem' => 'starttime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:starttime_formlabel,endtime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:endtime_formlabel',
        ],
    ],
    'columns' => [
        'crdate' => [
            'label' => 'crdate',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'tstamp' => [
            'label' => 'tstamp',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'location' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_location.location',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'street' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_location.street',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'house_number' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_location.house_number',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'trim',
            ],
        ],
        'zip' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_location.zip',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'trim',
            ],
        ],
        'city' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_location.city',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'country' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_location.country',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => '',
                        'value' => '',
                    ],
                ],
                'itemsProcFunc' => CountryItems::class . '->addCountries',
                'sortItems' => [
                    'label' => 'asc',
                ],
                'default' => $extConf->getDefaultCountry(),
            ],
        ],
        'link' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_location.link',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_events2_domain_model_link',
                'maxitems' => 1,
                'minitems' => 0,
                'appearance' => [
                    'levelLinksPosition' => 'top',
                    'newRecordLinkAddTitle' => true,
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1,
                ],
                'default' => 0,
            ],
        ],
    ],
];

unset($extConf);
return $tx_events2_domain_model_event;
