<?php

namespace JWeiland\Events2\UserFunc;

/*
 * This file is part of the TYPO3 CMS project.
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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SetTitleForDetailPage
{
    /**
     * @var ContentObjectRenderer
     */
    public $cObj;

    /**
     * Render page title for detail page
     *
     * @param string $content
     * @param array $conf
     *
     * @return string
     */
    public function render($content, $conf)
    {
        $gp = GeneralUtility::_GPmerged('tx_events2_events');
        if ($this->isValidRequest($gp)) {
            $dayRecord = $this->getDayRecord((int)$gp['day']);
            if (!empty($dayRecord)) {
                $date = new \DateTime(date('c', (int)$dayRecord['day']));
                $eventRecord = $this->getEventRecord((int)$dayRecord['event']);
                if (!empty($eventRecord)) {
                    $content = sprintf(
                        '%s - %s',
                        trim($eventRecord['title']),
                        $date->format('d.m.Y')
                    );
                }
            }
        }

        return $content;
    }

    /**
     * Check, if current request is valid
     *
     * @param array $gp
     *
     * @return bool
     */
    protected function isValidRequest($gp)
    {
        if (!is_array($gp)) {
            return false;
        }

        if (
            !isset($gp['controller']) ||
            !isset($gp['action']) ||
            !isset($gp['day'])
        ) {
            return false;
        }

        if (
            !MathUtility::canBeInterpretedAsInteger($gp['day']) ||
            (int)$gp['day'] <= 0
        ) {
            return false;
        }

        return true;
    }

    /**
     * Get day record
     *
     * @param int $uid
     *
     * @return array|false
     */
    protected function getDayRecord($uid)
    {
        $row = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            'uid, event, day',
            'tx_events2_domain_model_day',
            'uid=' . (int)$uid .
            $this->cObj->enableFields('tx_events2_domain_model_day')
        );
        if (is_null($row)) {
            $row = false;
        }
        return $row;
    }

    /**
     * Get event record
     *
     * @param int $uid
     *
     * @return array|false
     */
    protected function getEventRecord($uid)
    {
        $row = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            'uid, title',
            'tx_events2_domain_model_event',
            'uid=' . (int)$uid .
            $this->cObj->enableFields('tx_events2_domain_model_event')
        );
        if (is_null($row)) {
            $row = false;
        }
        return $row;
    }

    /**
     * Get TYPO3s Database Connection
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
