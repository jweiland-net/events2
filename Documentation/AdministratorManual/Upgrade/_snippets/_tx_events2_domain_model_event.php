<?php

use MyVendor\MySitepackage\Slug\EventSlugModifier;

defined('TYPO3') or die();

$GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['path_segment']['config']
    ['generatorOptions']['postModifiers'][] = EventSlugModifier::class . '->modify';
