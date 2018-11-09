<?php

namespace JWeiland\Events2\Controller;

/*
 * This file is part of the events2 project.
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

use JWeiland\Events2\Configuration\ExtConf;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Filter;
use JWeiland\Events2\Domain\Repository\CategoryRepository;
use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Domain\Repository\LinkRepository;
use JWeiland\Events2\Domain\Repository\LocationRepository;
use JWeiland\Events2\Domain\Repository\OrganizerRepository;
use JWeiland\Events2\Domain\Repository\UserRepository;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Session;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator;
use TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator;
use TYPO3\CMS\Extbase\Validation\Validator\RegularExpressionValidator;

/**
 * A collection of various helper methods and inject methods to keep
 * our Action Controllers small and clean
 */
class AbstractController extends ActionController
{
    /**
     * @var \TYPO3\CMS\Core\Mail\MailMessage
     */
    protected $mail;

    /**
     * @var \JWeiland\Events2\Configuration\ExtConf
     */
    protected $extConf;

    /**
     * eventRepository.
     *
     * @var \JWeiland\Events2\Domain\Repository\EventRepository
     */
    protected $eventRepository;

    /**
     * dayRepository.
     *
     * @var \JWeiland\Events2\Domain\Repository\DayRepository
     */
    protected $dayRepository;

    /**
     * locationRepository.
     *
     * @var \JWeiland\Events2\Domain\Repository\LocationRepository
     */
    protected $locationRepository;

    /**
     * organizerRepository
     *
     * @var \JWeiland\Events2\Domain\Repository\OrganizerRepository
     */
    protected $organizerRepository;

    /**
     * categoryRepository.
     *
     * @var \JWeiland\Events2\Domain\Repository\CategoryRepository
     */
    protected $categoryRepository;

    /**
     * userRepository.
     *
     * @var \JWeiland\Events2\Domain\Repository\UserRepository
     */
    protected $userRepository;

    /**
     * persistenceManager.
     *
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * Persistence session.
     *
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\Session
     */
    protected $session;

    /**
     * inject persistenceManager.
     *
     * @param PersistenceManagerInterface $persistenceManager
     */
    public function injectPersistenceManager(PersistenceManagerInterface $persistenceManager)
    {
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * inject mail.
     *
     * @param MailMessage $mail
     */
    public function injectMail(MailMessage $mail)
    {
        $this->mail = $mail;
    }

    /**
     * inject extConf.
     *
     * @param ExtConf $extConf
     */
    public function injectExtConf(ExtConf $extConf)
    {
        $this->extConf = $extConf;
    }

    /**
     * inject event repository.
     *
     * @param EventRepository $eventRepository
     */
    public function injectEventRepository(EventRepository $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }

    /**
     * inject day repository.
     *
     * @param DayRepository $dayRepository
     */
    public function injectDayRepository(DayRepository $dayRepository)
    {
        $this->dayRepository = $dayRepository;
    }

    /**
     * inject location repository.
     *
     * @param LocationRepository $locationRepository
     */
    public function injectLocationRepository(LocationRepository $locationRepository)
    {
        $this->locationRepository = $locationRepository;
    }

    /**
     * inject organizerRepository
     *
     * @param OrganizerRepository $organizerRepository
     */
    public function injectOrganizerRepository(OrganizerRepository $organizerRepository)
    {
        $this->organizerRepository = $organizerRepository;
    }

    /**
     * inject category repository.
     *
     * @param CategoryRepository $categoryRepository
     */
    public function injectCategoryRepository(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * inject user repository.
     *
     * @param UserRepository $userRepository
     */
    public function injectUserRepository(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * inject session.
     *
     * @param Session $session
     */
    public function injectSession(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Pre configure configuration
     *
     * @param ConfigurationManagerInterface $configurationManager
     * @return void
     * @throws \Exception
     */
    public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager)
    {
        $this->configurationManager = $configurationManager;

        $typoScriptSettings = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
            'events2',
            'events2_event'
        );
        if (empty($typoScriptSettings['settings'])) {
            throw new \Exception('You have forgotten to add TS-Template of events2', 1474533307);
        }
        $mergedFlexFormSettings = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS
        );

        // if FlexForm setting is empty and value is available in TS
        foreach ($typoScriptSettings['settings'] as $fieldName => $value) {
            if (
                $mergedFlexFormSettings[$fieldName] === '0' ||
                (
                    is_string($mergedFlexFormSettings[$fieldName]) &&
                    strlen($mergedFlexFormSettings[$fieldName]) === 0
                )
            ) {
                $mergedFlexFormSettings[$fieldName] = $value;
            }
        }
        $this->settings = $mergedFlexFormSettings;
    }

    /**
     * PreProcessing of all actions.
     *
     * @return void
     */
    public function initializeAction()
    {
        $this->eventRepository->setSettings($this->settings);
        $this->dayRepository->setSettings($this->settings);

        // if this value was not set, then it will be filled with 0
        // but that is not good, because UriBuilder accepts 0 as pid, so it's better to set it to NULL
        if (empty($this->settings['pidOfDetailPage'])) {
            $this->settings['pidOfDetailPage'] = null;
        }
        if (empty($this->settings['pidOfSearchPage'])) {
            $this->settings['pidOfSearchPage'] = null;
        }
        if (empty($this->settings['pidOfLocationPage'])) {
            $this->settings['pidOfLocationPage'] = null;
        }
        if (empty($this->settings['pidOfListPage'])) {
            $this->settings['pidOfListPage'] = null;
        }
    }

    /**
     * Initializes the view before invoking an action method.
     *
     * Override this method to solve assign variables common for all actions
     * or prepare the view in another way before the action is called.
     *
     * @param ViewInterface $view The view to be initialized
     * @return void
     */
    protected function initializeView(ViewInterface $view)
    {
        $this->view->assign('data', $this->configurationManager->getContentObject()->data);
        $this->view->assign('extConf', $this->extConf);
        $this->view->assign('jsVariables', json_encode($this->getJsVariables()));
        if ($this->settings['showFilterForOrganizerInFrontend']) {
            $this->view->assign('organizers', $this->organizerRepository->getOrganizersForFilter());
        }
    }

    /**
     * Create an array with mostly needed variables for JavaScript.
     * That way we don't need JavaScript parts in our templates.
     * I have separated this method to its own method as we have to override these variables
     * in SearchController and I can read them from View after variables are already assigned.
     *
     * @param array $override
     * @return array
     */
    protected function getJsVariables(array $override = [])
    {
        $jsVariables = [
            'settings' => $this->settings,
            'data' => $this->configurationManager->getContentObject()->data,
            'localization' => [
                'locationFail' => LocalizationUtility::translate('error.locationFail', 'events2'),
                'remainingText' => LocalizationUtility::translate('remainingLetters', 'events2')
            ]
        ];
        ArrayUtility::mergeRecursiveWithOverrule($jsVariables, $override);

        return $jsVariables;
    }

    /**
     * Validate filter
     * Create empty filter if not valid
     * Assign filter to view
     *
     * @param Filter|null $filter
     * @return Filter
     */
    protected function validateAndAssignFilter($filter)
    {
        if (
            !$filter instanceof Filter ||
            $filter === null
        ) {
            $filter = $this->objectManager->get(Filter::class);
        }
        $this->view->assign('filter', $filter);
        return $filter;
    }

    /**
     * Files will be uploaded in typeConverter
     * If newAction was called with a valid request something went wrong
     * In that case all uploaded files have to be deleted
     *
     * @param string $argument
     * @return void
     * @throws \Exception
     */
    protected function deleteUploadedFilesOnValidationErrors($argument)
    {
        if ($this->getControllerContext()->getRequest()->hasArgument($argument)) {
            /** @var \JWeiland\Events2\Domain\Model\Event $event */
            $event = $this->getControllerContext()->getRequest()->getArgument($argument);
            if ($event instanceof Event) {
                $images = $event->getImages();
                if (count($images)) {
                    /** @var \TYPO3\CMS\Extbase\Domain\Model\FileReference $image */
                    foreach ($images as $image) {
                        $orig = $image->getOriginalResource();
                        $orig->delete();
                    }
                }
            }
        }
    }

    /**
     * Remove videoLink if empty
     * Add special validation for videolink
     * I can't add this validation to Linkmodel, as such a validation would be also valid for organizer link.
     *
     * @return void
     * @throws \Exception
     */
    protected function addValidationForVideoLink()
    {
        if (
            $this->request->hasArgument('event') &&
            $eventRaw = $this->request->getArgument('event') &&
            empty($eventRaw['videoLink']['link'])
        ) {
            // create a new RegExpValidator for property link
            $regExpValidator = $this->objectManager->get(RegularExpressionValidator::class, [
                'regularExpression' => '~^(|http:|https:)//(|www.)youtube(.*?)(v=|embed/)([a-zA-Z0-9_-]+)~i',
            ]);
            $genericObjectValidator = $this->objectManager->get(GenericObjectValidator::class);
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

    /**
     * This is a workaround to help controller actions to find (hidden) events.
     *
     * @return void
     * @throws \Exception
     */
    protected function registerEventFromRequest()
    {
        $eventRaw = $this->request->getArgument('event');
        if (is_array($eventRaw)) {
            // get event from form ($_POST)
            $event = $this->eventRepository->findHiddenEntryByUid($eventRaw['__identity']);
        } else {
            // get event from UID
            $event = $this->eventRepository->findHiddenEntryByUid($eventRaw);
        }
        $this->session->registerObject($event, $event->getUid());
    }

    /**
     * delete videoLink if empty
     * Extbase can not set deleted=1 itself.
     *
     * @param \JWeiland\Events2\Domain\Model\Event $event
     * @return void
     * @throws \Exception
     */
    protected function deleteVideoLinkIfEmpty(Event $event)
    {
        $linkText = $event->getVideoLink()->getLink();
        if (empty($linkText)) {
            $linkRepository = $this->objectManager->get(LinkRepository::class);
            $linkRepository->remove($event->getVideoLink());
            $event->setVideoLink(null);
        }
    }

    /**
     * add organizer.
     *
     * In a HTML-Template you can change the user uid if you want
     * So it's better to add the organizer here in PHP
     *
     * @param string $argument
     * @return bool
     * @throws \Exception
     */
    protected function addOrganizer($argument)
    {
        if ($this->request->hasArgument($argument)) {
            /** @var array $event */
            $event = $this->request->getArgument($argument);
            if (!isset($event['organizer'])) {
                $organizerOfCurrentUser = $this->userRepository->getFieldFromUser('tx_events2_organizer');
                if (MathUtility::canBeInterpretedAsInteger($organizerOfCurrentUser)) {
                    $event['organizer'] = $organizerOfCurrentUser;
                    // per default it is not allowed to add new Arguments manually. So we have to register them.
                    // allow mapping of organizer
                    $this->arguments
                        ->getArgument($argument)
                        ->getPropertyMappingConfiguration()
                        ->allowProperties('organizer');
                    // allow creation
                    $this->arguments
                        ->getArgument($argument)
                        ->getPropertyMappingConfiguration()
                        ->forProperty('organizer')
                        ->setTypeConverterOption(
                            'TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter',
                            PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED,
                            true
                        )
                        ->allowProperties('organizer');
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
}
