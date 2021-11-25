<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Event;

use JWeiland\Events2\Controller\DayController;
use JWeiland\Events2\Controller\EventController;
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Request;

/**
 * Post process controller actions which does not assign any variables to view.
 * Often used by controller actions like "update" or "create" which redirects after success.
 */
class PostProcessControllerActionEvent implements ControllerActionEventInterface
{
    /**
     * @var ActionController|EventController|DayController
     */
    protected $controller;

    protected ?Event $event;

    protected ?Day $day;

    protected array $settings;

    public function __construct(
        ActionController $controller,
        ?Event $event,
        ?Day $day,
        array $settings
    ) {
        $this->controller = $controller;
        $this->event = $event;
        $this->day = $day;
        $this->settings = $settings;
    }

    public function getController(): ActionController
    {
        return $this->controller;
    }

    public function getEventController(): EventController
    {
        return $this->controller;
    }

    public function getDayController(): DayController
    {
        return $this->controller;
    }

    public function getRequest(): Request
    {
        return $this->getController()->getControllerContext()->getRequest();
    }

    public function getControllerName(): string
    {
        return $this->getRequest()->getControllerName();
    }

    public function getActionName(): string
    {
        return $this->getRequest()->getControllerActionName();
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function getDay(): ?Day
    {
        return $this->day;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }
}
