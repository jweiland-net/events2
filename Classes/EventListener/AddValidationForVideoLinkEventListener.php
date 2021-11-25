<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\EventListener;

use JWeiland\Events2\Event\PreProcessControllerActionEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator;
use TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator;
use TYPO3\CMS\Extbase\Validation\Validator\RegularExpressionValidator;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;

/*
 * Remove videoLink if empty.
 * Add special validation for VideoLink id exists.
 * I can't add this validation to LinkModel, as such a validation would be also valid for organizer link.
 */
class AddValidationForVideoLinkEventListener extends AbstractControllerEventListener
{
    protected array $allowedControllerActions = [
        'Event' => [
            'create',
            'update'
        ]
    ];

    public function __invoke(PreProcessControllerActionEvent $event): void
    {
        if (
            $this->isValidRequest($event)
            && $event->getRequest()->hasArgument('event')
            && ($eventRaw = $event->getRequest()->getArgument('event'))
            && empty($eventRaw['videoLink']['link'])
        ) {
            /** @var ValidatorInterface $regExpValidator */
            $regExpValidator = GeneralUtility::makeInstance(RegularExpressionValidator::class, [
                'regularExpression' => '~^(|http:|https:)//(|www.)youtube(.*?)(v=|embed/)([a-zA-Z0-9_-]+)~i',
            ]);
            /** @var GenericObjectValidator $genericObjectValidator */
            $genericObjectValidator = GeneralUtility::makeInstance(GenericObjectValidator::class);
            $genericObjectValidator->addPropertyValidator('link', $regExpValidator);

            // modify current validator of event
            /** @var ConjunctionValidator $eventValidator */
            $eventValidator = $event->getArguments()->getArgument('event')->getValidator();
            $validators = $eventValidator->getValidators();
            $validators->rewind();
            $eventValidator = $validators->current();
            $validators = $eventValidator->getValidators();
            $validators->rewind();
            /** @var GenericObjectValidator $eventValidator */
            $eventValidator = $validators->current();
            $eventValidator->addPropertyValidator('videoLink', $genericObjectValidator);
        }
    }
}
