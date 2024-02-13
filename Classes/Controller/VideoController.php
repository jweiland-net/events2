<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Controller;

use JWeiland\Events2\Traits\InjectEventRepositoryTrait;
use Psr\Http\Message\ResponseInterface;

/**
 * A simple controller to show video-link as YouTube-Implementation
 */
class VideoController extends AbstractController
{
    use InjectEventRepositoryTrait;

    public function showAction(int $event): ResponseInterface
    {
        $this->postProcessAndAssignFluidVariables([
            'event' => $this->eventRepository->findByIdentifier($event),
        ]);

        return $this->htmlResponse();
    }
}
