<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Upgrade;

use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\AbstractListTypeToCTypeUpdate;

/**
 * With TYPO3 13 all plugins have to be declared as content elements (CType) insteadof "list_type"
 */
#[UpgradeWizard('events2_migratePluginsToContentElementsUpdate')]
class MigratePluginToContentElementUpgrade extends AbstractListTypeToCTypeUpdate
{
    protected function getListTypeToCTypeMapping(): array
    {
        return [
            'events2_list' => 'events2_list',
            'events2_show' => 'events2_show',
            'events2_management' => 'events2_management',
            'events2_calendar' => 'events2_calendar',
            'events2_searchform' => 'events2_searchform',
            'events2_searchresults' => 'events2_searchresults',
        ];
    }

    public function getTitle(): string
    {
        return '[events2] Migrate plugins to Content Elements';
    }

    public function getDescription(): string
    {
        return 'The modern way to register plugins for TYPO3 is to register them as content element types. ' .
            'Running this wizard will migrate all events2 plugins to content element (CType)';
    }
}
