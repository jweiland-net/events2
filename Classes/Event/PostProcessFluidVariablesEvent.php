<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Event;

use TYPO3\CMS\Extbase\Mvc\RequestInterface;

/**
 * Post process controller actions which assign fluid variables to view.
 * Often used by controller actions like "show" or "list". No redirects possible here.
 */
class PostProcessFluidVariablesEvent implements ControllerActionEventInterface
{
    public function __construct(
        protected RequestInterface $request,
        protected array $settings,
        protected array $fluidVariables
    ) {}

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function getControllerName(): string
    {
        return $this->request->getControllerName();
    }

    public function getActionName(): string
    {
        return $this->request->getControllerActionName();
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function getFluidVariables(): array
    {
        return $this->fluidVariables;
    }

    public function addFluidVariable(string $key, $value): void
    {
        $this->fluidVariables[$key] = $value;
    }
}
