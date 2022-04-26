<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Controller;

use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Helper\DownloadHelper;
use JWeiland\Events2\Helper\ICalendarHelper;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/*
 * Controller to deliver an iCal download
 */
class ICalController extends ActionController
{
    protected DayRepository $dayRepository;
    protected ICalendarHelper $iCalendarHelper;
    protected DownloadHelper $downloadHelper;

    public function injectDayRepository(DayRepository $dayRepository): void
    {
        $this->dayRepository = $dayRepository;
    }

    public function injectICalendarHelper(ICalendarHelper $iCalendarHelper): void
    {
        $this->iCalendarHelper = $iCalendarHelper;
    }

    public function injectDownloadHelper(DownloadHelper $downloadHelper): void
    {
        $this->downloadHelper = $downloadHelper;
    }

    public function downloadAction(int $dayUid): ResponseInterface
    {
        $day = $this->dayRepository->findByIdentifier($dayUid);
        if ($day instanceof Day) {
            return $this->downloadHelper->forceDownloadFile(
                null,
                $this->iCalendarHelper->buildICalExport($day),
                true,
                $this->iCalendarHelper->getEventUid($day) . '.ics'
            );
        }

        return new JsonResponse(
            [
                'error' => 'DayRecord ' . $day . ' for iCal download could not be found in our database.'
            ],
            500
        );
    }
}
