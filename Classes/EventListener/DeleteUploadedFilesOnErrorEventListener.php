<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\EventListener;

use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Event\PostProcessFluidVariablesEvent;
use JWeiland\Events2\Traits\IsValidEventListenerRequestTrait;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * Files will be uploaded in our own UploadMultipleFiles-TypeConverter.
 * If newAction was called and validation fails all uploaded files have to be deleted to prevent duplicates.
 *
 * @ToDo: I still need a solution for editAction
 */
#[AsEventListener('events2/deleteUploadedFilesOnError')]
final readonly class DeleteUploadedFilesOnErrorEventListener
{
    use IsValidEventListenerRequestTrait;

    protected const ALLOWED_CONTROLLER_ACTIONS = [
        'Management' => [
            'new',
        ],
    ];

    public function __invoke(PostProcessFluidVariablesEvent $controllerActionEvent): void
    {
        if (
            $this->isValidRequest($controllerActionEvent)
            && $controllerActionEvent->getRequest()->hasArgument('event')
        ) {
            $eventObject = $controllerActionEvent->getRequest()->getArgument('event');
            if ($eventObject instanceof Event) {
                $images = $eventObject->getImages();
                foreach ($images as $image) {
                    $orig = $image->getOriginalResource();
                    $orig->delete();
                }
            }
        }
    }
}
