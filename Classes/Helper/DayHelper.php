<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Helper;

use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Repository\DayRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/*
 * Helper class containing various methods to work with Day models
 */
class DayHelper
{
    protected DayRepository $dayRepository;

    public function __construct(DayRepository $dayRepository)
    {
        $this->dayRepository = $dayRepository;
    }
    /**
     * Get day from URI
     * We can't set $day as parameter in showAction($day), because this action is of controller Calendar and not Event.
     */
    public function getDayFromUri(): ?Day
    {
        $day = null;
        // get parameters of event-plugin-namespace
        $pluginParameters = GeneralUtility::_GPmerged('tx_events2_list');
        if (
            is_array($pluginParameters) &&
            array_key_exists('day', $pluginParameters) &&
            MathUtility::canBeInterpretedAsInteger($pluginParameters['day'])
        ) {
            /** @var Day $day */
            $day = $this->dayRepository->findByIdentifier((int)$pluginParameters['day']);
        }

        return $day;
    }
}
