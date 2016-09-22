<?php

namespace JWeiland\Events2\Controller;

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

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class DayController extends AbstractController
{
    /**
     * @var \JWeiland\Events2\Domain\Repository\DayRepository
     */
    protected $dayRepository = null;
    
    /**
     * inject dayRepository
     *
     * @param \JWeiland\Events2\Domain\Repository\DayRepository $dayRepository
     * @return void
     */
    public function injectDayRepository(\JWeiland\Events2\Domain\Repository\DayRepository $dayRepository)
    {
        $this->dayRepository = $dayRepository;
    }
    
    /**
     * action show.
     *
     * Hint: I call showAction with int instead of DomainModel
     * to prevent that recursive validators will be called
     *
     * @param int $day
     * @return void
     */
    public function showAction($day)
    {
        /** @var \JWeiland\Events2\Domain\Model\Day $dayObject */
        $dayObject = $this->dayRepository->findByIdentifier($day);
        $dayObject->getEvents(
            GeneralUtility::trimExplode(',', $this->settings['categories'], true),
            $this->getStoragePids()
        );
        $this->view->assign('day', $dayObject);
    }
    
    /**
     * Get configured Storage PIDs
     *
     * @return array
     */
    protected function getStoragePids()
    {
        $query = $this->dayRepository->createQuery();
        return $query->getQuerySettings()->getStoragePageIds();
    }
}
