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
use JWeiland\Events2\Domain\Model\Filter;
use JWeiland\Events2\Domain\Repository\CategoryRepository;
use JWeiland\Events2\Domain\Repository\DayRepository;
use JWeiland\Events2\Domain\Repository\EventRepository;
use JWeiland\Events2\Domain\Repository\LinkRepository;
use JWeiland\Events2\Domain\Repository\LocationRepository;
use JWeiland\Events2\Domain\Repository\OrganizerRepository;
use JWeiland\Events2\Domain\Repository\UserRepository;
use JWeiland\Events2\Service\TypoScriptService;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Extbase\Validation\Validator\RegularExpressionValidator;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;
use TYPO3\CMS\Extbase\Validation\ValidatorResolver;

/*
 * A collection of various helper methods and inject methods to keep
 * our Action Controllers small and clean
 */
class AbstractController extends ActionController
{
    /**
     * @var MailMessage
     */
    protected $mail;

    /**
     * @var ExtConf
     */
    protected $extConf;

    /**
     * @var EventRepository
     */
    protected $eventRepository;

    /**
     * @var DayRepository
     */
    protected $dayRepository;

    /**
     * @var LocationRepository
     */
    protected $locationRepository;

    /**
     * @var OrganizerRepository
     */
    protected $organizerRepository;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var Session
     */
    protected $session;

    public function __construct(
        ?ExtConf $extConf = null,
        ?MailMessage $mailMessage = null,
        ?UserRepository $userRepository = null
    ) {
        $this->extConf = $extConf ?? GeneralUtility::makeInstance(ExtConf::class);
        $this->mail = $mailMessage ?? GeneralUtility::makeInstance(MailMessage::class);
        $this->userRepository = $userRepository ?? GeneralUtility::makeInstance(UserRepository::class);
    }

    public function injectPersistenceManager(PersistenceManagerInterface $persistenceManager): void
    {
        $this->persistenceManager = $persistenceManager;
    }

    public function injectEventRepository(EventRepository $eventRepository): void
    {
        $this->eventRepository = $eventRepository;
    }

    public function injectDayRepository(DayRepository $dayRepository): void
    {
        $this->dayRepository = $dayRepository;
    }

    public function injectLocationRepository(LocationRepository $locationRepository): void
    {
        $this->locationRepository = $locationRepository;
    }

    public function injectOrganizerRepository(OrganizerRepository $organizerRepository): void
    {
        $this->organizerRepository = $organizerRepository;
    }

    public function injectCategoryRepository(CategoryRepository $categoryRepository): void
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function injectSession(Session $session): void
    {
        $this->session = $session;
    }

    /**
     * @param ConfigurationManagerInterface $configurationManager
     * @throws \Exception
     */
    public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager): void
    {
        $this->configurationManager = $configurationManager;

        $typoScriptSettings = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
            'events2',
            'events2_event' // invalid plugin name, to get fresh unmerged settings
        );
        if (empty($typoScriptSettings['settings'])) {
            throw new \Exception('You have forgotten to add TS-Template of events2', 1580294227);
        }
        $mergedFlexFormSettings = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS
        );

        // start override
        $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
        $typoScriptService->override(
            $mergedFlexFormSettings,
            $typoScriptSettings['settings']
        );
        $this->settings = $mergedFlexFormSettings;
    }

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
    protected function getJsVariables(array $override = []): array
    {
        // remove pi_flexform from data, as it contains XML/HTML which can be indexed through Solr
        $data = $this->configurationManager->getContentObject()->data;
        unset($data['pi_flexform']);

        $jsVariables = [
            'settings' => $this->settings,
            'data' => $data,
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
    protected function validateAndAssignFilter(?Filter $filter): Filter
    {
        if ($filter === null) {
            $filter = GeneralUtility::makeInstance(Filter::class);
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
     */
    protected function deleteUploadedFilesOnValidationErrors(string $argument): void
    {
        if ($this->getControllerContext()->getRequest()->hasArgument($argument)) {
            /** @var Event $event */
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
            /** @var ValidatorInterface $genericObjectValidator */
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

    /**
     * This is a workaround to help controller actions to find (hidden) events.
     */
    protected function registerEventFromRequest(): void
    {
        $eventRaw = $this->request->getArgument('event');
        if (is_array($eventRaw)) {
            // get event from form ($_POST)
            $event = $this->eventRepository->findHiddenEntry($eventRaw['__identity']);
        } else {
            // get event from UID
            $event = $this->eventRepository->findHiddenEntry($eventRaw);
        }
        $this->session->registerObject($event, $event->getUid());
    }

    /**
     * Delete VideoLink if empty
     * Extbase can not set deleted=1 itself.
     *
     * @param Event $event
     */
    protected function deleteVideoLinkIfEmpty(Event $event): void
    {
        $linkText = $event->getVideoLink()->getLink();
        if (empty($linkText)) {
            $linkRepository = $this->objectManager->get(LinkRepository::class);
            $linkRepository->remove($event->getVideoLink());
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
