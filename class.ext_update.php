<?php
namespace JWeiland\Events2;

/*
 * This file is part of the events2 project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageRendererResolver;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Update class for the extension manager.
 */
class ext_update
{
    /**
     * Array of flash messages (params) array[][status,title,message]
     *
     * @var array
     */
    protected $messageArray = [];

    /**
     * Main update function called by the extension manager.
     *
     * @return string
     */
    public function main()
    {
        $this->processUpdates();
        return $this->generateOutput();
    }

    /**
     * Called by the extension manager to determine if the update menu entry
     * should by showed.
     *
     * @return bool
     */
    public function access()
    {
        $showAccess = false;

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $amountOfRecords = $queryBuilder
            ->count('*')
            ->from('sys_template')
            ->where(
                $queryBuilder->expr()->like(
                    'include_static_file',
                    $queryBuilder->createNamedParameter(
                        '%EXT:events2/Configuration/TypoScript/Typo384%',
                        \PDO::PARAM_STR
                    )
                )
            )
            ->execute()
            ->fetchColumn(0);

        if ((bool)$amountOfRecords) {
            $showAccess = true;
        }
        return $showAccess;
    }

    /**
     * The actual update function. Add your update task in here.
     *
     * @return void
     */
    protected function processUpdates()
    {
        $this->migrateSysTemplates();
    }

    /**
     * Migrate old sys_template paths to new location
     */
    protected function migrateSysTemplates()
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $sysTemplates = $queryBuilder
            ->select('uid', 'include_static_file')
            ->from('sys_template')
            ->where(
                $queryBuilder->expr()->like(
                    'include_static_file',
                    $queryBuilder->createNamedParameter(
                        '%EXT:events2/Configuration/TypoScript/Typo384%',
                        \PDO::PARAM_STR
                    )
                )
            )
            ->execute()
            ->fetchAll();

        if ($sysTemplates === false) {
            $sysTemplates = [];
        }

        foreach ($sysTemplates as $sysTemplate) {
            $sysTemplate['include_static_file'] = str_replace(
                'EXT:events2/Configuration/TypoScript/Typo384',
                'EXT:events2/Configuration/TypoScript',
                $sysTemplate['include_static_file']
            );

            $connection = $this->getConnectionPool()->getConnectionForTable('sys_template');
            $connection->update(
                'sys_template',
                $sysTemplate,
                ['uid' => $sysTemplate['uid']],
                [Connection::PARAM_INT]
            );
        }

        $this->messageArray[] = [
            FlashMessage::OK,
            'Migration successful',
            sprintf(
                'We have migrated %d sys_template records',
                count($sysTemplates)
            )
        ];
    }

    /**
     * Generates output by using flash messages
     *
     * @return string
     */
    protected function generateOutput()
    {
        $output = '';
        foreach ($this->messageArray as $messageItem) {
            /** @var FlashMessage $flashMessage */
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $messageItem[2],
                $messageItem[1],
                $messageItem[0]
            );

            $output .= GeneralUtility::makeInstance(FlashMessageRendererResolver::class)
                ->resolve()
                ->render([$flashMessage]);
        }
        return $output;
    }

    /**
     * Get TYPO3s Connection Pool
     *
     * @return ConnectionPool
     */
    protected function getConnectionPool()
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
