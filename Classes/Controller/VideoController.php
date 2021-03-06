<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Controller;

use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Service\TypoScriptService;

/*
 * A simple controller to show video-link as YouTube-Implementation
 */
class VideoController extends AbstractController
{
    /**
     * @var EventRepository
     */
    protected $eventRepository;

    public function __construct(
        EventRepository $eventRepository,
        TypoScriptService $typoScriptService,
        ExtConf $extConf
    ) {
        parent::__construct($typoScriptService, $extConf);

        $this->eventRepository = $eventRepository;
    }

    /**
     * @param int $event
     */
    public function showAction(int $event): void
    {
        $this->postProcessAndAssignFluidVariables([
            'event' => $this->eventRepository->findByIdentifier($event)
        ]);
    }
}
