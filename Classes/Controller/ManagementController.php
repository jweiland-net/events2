<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Controller;

use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Service\ManagementAccessService;
use JWeiland\Events2\Traits\InjectCacheServiceTrait;
use JWeiland\Events2\Traits\InjectEventRepositoryTrait;
use JWeiland\Events2\Traits\InjectMailMessageTrait;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\Controller\ErrorController;

/**
 * The ManagementController lists the events of the current frontend user,
 * renders the event form based on EXT:form to create and edit events and
 * lets moderators confirm and activate events created in frontend.
 * For a plain listing of events, please visit DayController
 */
class ManagementController extends AbstractController
{
    use InjectCacheServiceTrait;
    use InjectEventRepositoryTrait;
    use InjectMailMessageTrait;

    public function __construct(
        protected readonly ManagementAccessService $managementAccessService,
        protected readonly ErrorController $errorController,
    ) {}

    public function initializeListMyEventsAction(): void
    {
        $this->preProcessControllerAction();
    }

    public function listMyEventsAction(): ResponseInterface
    {
        $events = $this->eventRepository->findMyEvents();
        $this->postProcessAndAssignFluidVariables([
            'events' => $events,
        ]);
        $this->cacheService->addPageCacheTagsByQuery($events->getQuery());

        return $this->htmlResponse();
    }

    public function initializePerformAction(): void
    {
        $this->preProcessControllerAction();
    }

    #[Extbase\IgnoreValidation(['value' => 'event'])]
    public function performAction(?Event $event = null): ResponseInterface
    {
        return $this->htmlResponse();
    }

    public function initializeConfirmActivationAction(): void
    {
        $this->preProcessControllerAction();
    }

    /**
     * Mail security scanners and link previews open the links of a mail
     * automatically. So the activation link of the mail shows this summary
     * of the event only. Its form activates the event via POST.
     */
    #[Extbase\IgnoreValidation(['value' => 'event'])]
    public function confirmActivationAction(Event $event): ResponseInterface
    {
        $this->postProcessAndAssignFluidVariables([
            'event' => $event,
            'moderationToken' => $this->managementAccessService->getValidModerationToken(
                $this->request,
                (int)$event->getUid(),
            ),
        ]);

        return $this->htmlResponse();
    }

    public function initializeActivateAction(): void
    {
        $this->preProcessControllerAction();
    }

    /**
     * The event is activated via POST only. GET requests (e.g. activation
     * links of former mails) are redirected to the confirmation page.
     * We work with the event UID here, so no property of the event is mapped
     * and validated. Activation changes the "hidden" state only.
     *
     * @throws PropagateResponseException
     */
    public function activateAction(int $event): ResponseInterface
    {
        if ($this->request->getMethod() !== 'POST') {
            return $this->redirect('confirmActivation', null, null, [
                'event' => $event,
                'moderationToken' => $this->managementAccessService->getValidModerationToken($this->request, $event),
            ]);
        }

        $eventObject = $this->getEventToActivate($event);
        $eventObject->setHidden(false);
        $this->eventRepository->update($eventObject);

        $this->postProcessAndAssignFluidVariables([
            'event' => $eventObject,
        ]);

        $this->addFlashMessage(LocalizationUtility::translate('email.subject.activate', 'events2'));

        $this->sendMail('activate');

        return $this->redirect('list', 'Day');
    }

    /**
     * Unknown events result in the 404 error handling of the site
     *
     * @throws PropagateResponseException
     */
    protected function getEventToActivate(int $eventUid): Event
    {
        $event = $this->eventRepository->findHiddenObject($eventUid);
        if (!$event instanceof Event) {
            throw new PropagateResponseException(
                $this->errorController->pageNotFoundAction($this->request, 'Event not found'),
                1762358401,
            );
        }

        return $event;
    }

    protected function sendMail(string $subjectKey): bool
    {
        $this->mailMessage->setFrom($this->extConf->getEmailFromAddress(), $this->extConf->getEmailFromName());
        $this->mailMessage->setTo($this->extConf->getEmailToAddress(), $this->extConf->getEmailToName());
        $this->mailMessage->setSubject(
            LocalizationUtility::translate(
                'email.subject.' . $subjectKey,
                'events2',
            ),
        );

        $this->mailMessage->html($this->view->render());

        return $this->mailMessage->send();
    }
}
