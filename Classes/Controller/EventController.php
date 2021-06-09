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
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Link;
use JWeiland\Events2\Domain\Model\Search;
use JWeiland\Events2\Domain\Repository\CategoryRepository;
use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Domain\Repository\LinkRepository;
use JWeiland\Events2\Domain\Repository\LocationRepository;
use JWeiland\Events2\Domain\Repository\UserRepository;
use JWeiland\Events2\Helper\HiddenObjectHelper;
use JWeiland\Events2\Helper\PathSegmentHelper;
use JWeiland\Events2\Property\TypeConverter\UploadMultipleFilesConverter;
use JWeiland\Events2\Service\DayRelationService;
use JWeiland\Events2\Utility\CacheUtility;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\CMS\Extbase\Property\TypeConverterInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator;
use TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Extbase\Validation\Validator\RegularExpressionValidator;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;
use TYPO3\CMS\Extbase\Validation\ValidatorResolver;

/*
 * The EventController contains actions to search, create, activate, update and delete events.
 * For plain listing of events please visit DayController
 */
class EventController extends AbstractController
{
    /**
     * @var DayRepository
     */
    protected $dayRepository;

    /**
     * @var EventRepository
     */
    protected $eventRepository;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var LocationRepository
     */
    protected $locationRepository;

    /**
     * @var LinkRepository
     */
    protected $linkRepository;

    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var MailMessage
     */
    protected $mail;

    /**
     * @var ExtConf
     */
    protected $extConf;

    public function __construct(
        DayRepository $dayRepository,
        EventRepository $eventRepository,
        CategoryRepository $categoryRepository,
        LocationRepository $locationRepository,
        LinkRepository $linkRepository,
        UserRepository $userRepository,
        PersistenceManagerInterface $persistenceManager,
        MailMessage $mail,
        ExtConf $extConf
    ) {
        $this->dayRepository = $dayRepository;
        $this->eventRepository = $eventRepository;
        $this->categoryRepository = $categoryRepository;
        $this->locationRepository = $locationRepository;
        $this->linkRepository = $linkRepository;
        $this->userRepository = $userRepository;
        $this->persistenceManager = $persistenceManager;
        $this->mail = $mail;
        $this->extConf = $extConf;
    }

    public function initializeObject()
    {
        $this->dayRepository->setSettings($this->settings);
    }

    /**
     * We have a self-build form based on method GET.
     * That's why we have to manually allow some form-elements.
     */
    public function initializeListSearchResultsAction(): void
    {
        $this->arguments->getArgument('search')->getPropertyMappingConfiguration()->setTypeConverterOptions(
            PersistentObjectConverter::class,
            [
                PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => true,
            ]
        );
        $this->arguments->getArgument('search')->getPropertyMappingConfiguration()->allowAllProperties();
    }

    public function listSearchResultsAction(Search $search): void
    {
        $days = $this->dayRepository->searchEvents($search);
        $this->view->assign('days', $days);
        CacheUtility::addPageCacheTagsByQuery($days->getQuery());
    }

    public function listMyEventsAction(): void
    {
        $events = $this->eventRepository->findMyEvents();
        $this->view->assign('events', $events);
        CacheUtility::addPageCacheTagsByQuery($events->getQuery());
    }

    public function newAction(): void
    {
        $this->deleteUploadedFilesOnValidationErrors('event');
        $event = GeneralUtility::makeInstance(Event::class);
        $categories = $this->categoryRepository->getCategories(
            $this->settings['selectableCategoriesForNewEvents']
        );

        if (!$categories->count()) {
            $this->addFlashMessage('Dear Admin: You have forgotten to define some allowed categories in plugin configuration');
        }

        $assignedValues = [
            'event' => $event,
            'locations' => $this->locationRepository->findAll(),
            'selectableCategories' => $categories
        ];

        $this->view->assignMultiple(
            $this->emitActionSignal(
                'EventController',
                'newAction',
                $assignedValues
            )
        );
    }

    /**
     * Initialize create action.
     * We need this to create a DateTime-Object with time of midnight.
     */
    public function initializeCreateAction(): void
    {
        $this->addValidationForVideoLink();
        $this->addOrganizer('event');
        $this->applyLocationAsMandatoryIfNeeded();

        $eventMappingConfiguration = $this->arguments
            ->getArgument('event')
            ->getPropertyMappingConfiguration();

        $this->setDatePropertyFormat('eventBegin', $eventMappingConfiguration);
        $this->setDatePropertyFormat('eventEnd', $eventMappingConfiguration);
        $this->assignMediaTypeConverter('images', $eventMappingConfiguration, null);
    }

    /**
     * @param Event $event
     */
    public function createAction(Event $event): void
    {
        $event->setHidden(true);
        $event->setEventType($event->getEventEnd() ? 'duration' : 'single');
        $this->deleteVideoLinkIfEmpty($event);
        $this->eventRepository->add($event);
        $this->persistenceManager->persistAll();
        $this->addDayRelations($event);

        $pathSegmentHelper = GeneralUtility::makeInstance(PathSegmentHelper::class);
        $pathSegmentHelper->updatePathSegmentForEvent($event);
        $this->eventRepository->update($event);

        $signalArguments = $this->emitActionSignal(
            'EventController',
            'createAction',
            [
                'event' => $event
            ]
        );

        if (!$event->getPathSegment()) {
            throw new \Exception(
                'Path Segment of event is missing. Please check pathSegmentType in extension settings or used SignalSlots in create action',
                1611157656
            );
        }

        $this->sendMail('create', $signalArguments['event']);
        $this->addFlashMessage(LocalizationUtility::translate('eventCreated', 'events2'));
        $this->redirect('list', 'Day');
    }

    /**
     * @param int $event
     */
    public function editAction(int $event): void
    {
        $eventObject = $this->eventRepository->findHiddenObject($event);
        $categories = $this->categoryRepository->getCategories(
            $this->settings['selectableCategoriesForNewEvents']
        );

        if (!$categories->count()) {
            $this->addFlashMessage('Dear Admin: You have forgotten to define some allowed categories in plugin configuration');
        }

        $assignedValues = [
            'event' => $eventObject,
            'locations' => $this->locationRepository->findAll(),
            'selectableCategories' => $categories
        ];

        $this->view->assignMultiple(
            $this->emitActionSignal(
                'EventController',
                'editAction',
                $assignedValues
            )
        );
    }

    /**
     * Initialize update action
     * We need this to create a DateTime-Object with time of midnight.
     */
    public function initializeUpdateAction(): void
    {
        $hiddenObjectHelper = $this->objectManager->get(HiddenObjectHelper::class);
        $hiddenObjectHelper->registerHiddenObjectInExtbaseSession(
            $this->eventRepository,
            $this->request,
            'event'
        );
        $this->addValidationForVideoLink();
        $this->applyLocationAsMandatoryIfNeeded();

        /** @var Event $event */
        $event = $this->eventRepository->findByIdentifier(
            $this->request->getArgument('event')['__identity']
        );

        $eventMappingConfiguration = $this->arguments
            ->getArgument('event')
            ->getPropertyMappingConfiguration();

        $this->setDatePropertyFormat('eventBegin', $eventMappingConfiguration);
        $this->setDatePropertyFormat('eventEnd', $eventMappingConfiguration);
        $this->assignMediaTypeConverter('images', $eventMappingConfiguration, $event->getImages());
    }

    /**
     * @param Event $event
     */
    public function updateAction(Event $event): void
    {
        $isHidden = $event->getHidden();
        $event->setHidden(true);
        $this->deleteVideoLinkIfEmpty($event);
        $this->eventRepository->update($event);
        $this->persistenceManager->persistAll();
        $this->addDayRelations($event);

        $signalArguments = [
            'event' => $event
        ];

        $signalArguments = $this->emitActionSignal(
            'EventController',
            'updateAction',
            $signalArguments
        );

        // if editor edits this hidden record, mail should not be send
        if (!$isHidden) {
            $this->sendMail('update', $signalArguments['event']);
        }
        $this->addFlashMessage(LocalizationUtility::translate('eventUpdated', 'events2'));
        $this->redirect('listMyEvents', 'Event');
    }

    /**
     * @param int $event
     */
    public function deleteAction(int $event): void
    {
        $eventObject = $this->eventRepository->findByIdentifier($event);
        $this->eventRepository->remove($eventObject);

        $signalArguments = [
            'event' => $event
        ];

        $this->emitActionSignal(
            'EventController',
            'deleteAction',
            $signalArguments
        );

        $this->addFlashMessage(LocalizationUtility::translate('eventDeleted', 'events2'));
        $this->redirect('list', 'Day');
    }

    /**
     * @param int $event
     */
    public function activateAction(int $event): void
    {
        $eventObject = $this->eventRepository->findHiddenObject($event);
        $eventObject->setHidden(false);
        $this->eventRepository->update($eventObject);

        $assignedValues = [
            'event' => $eventObject
        ];

        $this->view->assignMultiple($this->emitActionSignal(
            'EventController',
            'activateAction',
            $assignedValues
        ));

        // send mail
        $this->mail->setFrom($this->extConf->getEmailFromAddress(), $this->extConf->getEmailFromName());
        $this->mail->setTo($this->extConf->getEmailToAddress(), $this->extConf->getEmailToName());
        $this->mail->setSubject(LocalizationUtility::translate('email.subject.activate', 'events2'));
        if (version_compare(TYPO3_branch, '10.0', '>=')) {
            $this->mail->html($this->view->render());
        } else {
            $this->mail->setBody($this->view->render(), 'text/html');
        }
        $this->mail->send();

        $this->redirect('list', 'Day');
    }

    protected function setDatePropertyFormat(
        string $property,
        PropertyMappingConfigurationInterface $configuration
    ): void {
        $configuration
            ->forProperty($property)
            ->setTypeConverterOption(
                DateTimeConverter::class,
                DateTimeConverter::CONFIGURATION_DATE_FORMAT,
                'd.m.Y'
            );
    }

    /**
     * Currently only "logo" and "images" are allowed properties.
     *
     * @param string $property
     * @param MvcPropertyMappingConfiguration $propertyMappingConfigurationForClub
     * @param mixed $converterOptionValue
     */
    protected function assignMediaTypeConverter(
        string $property,
        MvcPropertyMappingConfiguration $propertyMappingConfigurationForClub,
        $converterOptionValue
    ): void {
        if ($property === 'logo' || $property === 'images') {
            $className = UploadMultipleFilesConverter::class;
            $converterOptionName = 'IMAGES';
        } else {
            return;
        }

        /** @var TypeConverterInterface $typeConverter */
        $typeConverter = $this->objectManager->get($className);
        $propertyMappingConfigurationForMediaFiles = $propertyMappingConfigurationForClub
            ->forProperty($property)
            ->setTypeConverter($typeConverter);

        $propertyMappingConfigurationForMediaFiles->setTypeConverterOption(
            $className,
            'settings',
            $this->settings
        );

        if (!empty($converterOptionValue)) {
            // Do not use setTypeConverterOptions() as this will remove all existing options
            $propertyMappingConfigurationForMediaFiles->setTypeConverterOption(
                $className,
                $converterOptionName,
                $converterOptionValue
            );
        }
    }

    protected function addDayRelations(Event $event): void
    {
        $this->objectManager
            ->get(DayRelationService::class)
            ->createDayRelations($event->getUid());
    }

    protected function sendMail(string $subjectKey, Event $event): bool
    {
        $this->view->assign('event', $event);

        $this->mail->setFrom($this->extConf->getEmailFromAddress(), $this->extConf->getEmailFromName());
        $this->mail->setTo($this->extConf->getEmailToAddress(), $this->extConf->getEmailToName());
        $this->mail->setSubject(LocalizationUtility::translate('email.subject.' . $subjectKey, 'events2'));
        if (version_compare(TYPO3_branch, '10.0', '>=')) {
            $this->mail->html($this->view->render());
        } else {
            $this->mail->setBody($this->view->render(), 'text/html');
        }

        return (bool)$this->mail->send();
    }

    /**
     * Delete VideoLink if empty in Request.
     * Extbase can not set deleted=1 itself.
     *
     * @param Event $event
     */
    protected function deleteVideoLinkIfEmpty(Event $event): void
    {
        if (
            $event->getVideoLink() instanceof Link
            && empty($event->getVideoLink()->getLink())
        ) {
            $this->linkRepository->remove($event->getVideoLink());
            $event->setVideoLink(null);
        }
    }

    /**
     * Frontend insertion of events also has to respect location, if configured in ExtConf
     */
    protected function applyLocationAsMandatoryIfNeeded(): void
    {
        if ($this->extConf->getLocationIsRequired()) {
            /** @var ValidatorResolver $validatorResolver */
            $validatorResolver = $this->objectManager->get(ValidatorResolver::class);
            $notEmptyValidator = $validatorResolver->createValidator(NotEmptyValidator::class);

            /** @var ConjunctionValidator $eventValidator */
            $eventValidator = $this->arguments->getArgument('event')->getValidator();
            /** @var ConjunctionValidator $conjunctionValidator */
            $conjunctionValidator = $eventValidator->getValidators()->current();
            /** @var GenericObjectValidator $genericEventValidator */
            $genericEventValidator = $conjunctionValidator->getValidators()->current();
            $genericEventValidator->addPropertyValidator(
                'location',
                $notEmptyValidator
            );
        }
    }

    /**
     * Add organizer.
     *
     * In a HTML-Template you can change the user uid if you want
     * So it's better to add the organizer here in PHP
     *
     * @param string $argument
     * @return bool
     */
    protected function addOrganizer(string $argument): bool
    {
        if ($this->request->hasArgument($argument)) {
            $event = $this->request->getArgument($argument);
            if (is_array($event) && !array_key_exists('organizers', $event)) {
                $organizerOfCurrentUser = $this->userRepository->getFieldFromUser('tx_events2_organizer');
                if (MathUtility::canBeInterpretedAsInteger($organizerOfCurrentUser)) {
                    $event['organizers'][0] = $organizerOfCurrentUser;
                    // per default it is not allowed to add new Arguments manually. So we have to register them.
                    // allow mapping of organizer
                    $this->arguments
                        ->getArgument($argument)
                        ->getPropertyMappingConfiguration()
                        ->allowProperties('organizers');
                    // allow creation
                    $this->arguments
                        ->getArgument($argument)
                        ->getPropertyMappingConfiguration()
                        ->forProperty('organizers.*')
                        ->setTypeConverterOption(
                            'TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter',
                            PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED,
                            true
                        );
                    $this->request->setArgument($argument, $event);
                } else {
                    return false;
                }
            } else {
                return true;
            }
        }
        return false;
    }

    /**
     * Files will be uploaded in typeConverter
     * If newAction was called with a valid request something went wrong
     * In that case all uploaded files have to be deleted
     *
     * @param string $argument
     */
    protected function deleteUploadedFilesOnValidationErrors(string $argument): void
    {
        if ($this->getControllerContext()->getRequest()->hasArgument($argument)) {
            $event = $this->getControllerContext()->getRequest()->getArgument($argument);
            if ($event instanceof Event) {
                $images = $event->getImages();
                foreach ($images as $image) {
                    $orig = $image->getOriginalResource();
                    $orig->delete();
                }
            }
        }
    }

    /**
     * Remove videoLink if empty
     * Add special validation for VideoLink
     * I can't add this validation to LinkModel, as such a validation would be also valid for organizer link.
     */
    protected function addValidationForVideoLink(): void
    {
        if (
            $this->request->hasArgument('event') &&
            $eventRaw = $this->request->getArgument('event') &&
                empty($eventRaw['videoLink']['link'])
        ) {
            /** @var ValidatorInterface $regExpValidator */
            $regExpValidator = GeneralUtility::makeInstance(RegularExpressionValidator::class, [
                'regularExpression' => '~^(|http:|https:)//(|www.)youtube(.*?)(v=|embed/)([a-zA-Z0-9_-]+)~i',
            ]);
            /** @var GenericObjectValidator $genericObjectValidator */
            $genericObjectValidator = GeneralUtility::makeInstance(GenericObjectValidator::class);
            $genericObjectValidator->addPropertyValidator('link', $regExpValidator);

            // modify current validator of event
            $event = $this->arguments->getArgument('event');
            /** @var ConjunctionValidator $eventValidator */
            $eventValidator = $event->getValidator();
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
