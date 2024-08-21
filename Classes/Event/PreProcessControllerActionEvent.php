<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Event;

use TYPO3\CMS\Extbase\Mvc\Controller\Arguments;
use TYPO3\CMS\Extbase\Mvc\Request;

class PreProcessControllerActionEvent implements ControllerActionEventInterface
{
    protected Request $request;

    protected Arguments $arguments;

    protected array $settings = [];

    public function __construct(
        Request $request,
        Arguments $arguments,
        array $settings,
    ) {
        $this->request = $request;
        $this->arguments = $arguments;
        $this->settings = $settings;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    public function getControllerName(): string
    {
        return $this->request->getControllerName();
    }

    public function getActionName(): string
    {
        return $this->request->getControllerActionName();
    }

    public function getArguments(): Arguments
    {
        return $this->arguments;
    }

    public function setArguments(Arguments $arguments): void
    {
        $this->arguments = $arguments;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }
}
