<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/yellowpages2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Yellowpages2\Event;

use TYPO3\CMS\Extbase\Mvc\Request;

/**
 * Post process controller actions which assign fluid variables to view.
 * Often used by controller actions like "show" or "list". No redirects possible here.
 */
class PostProcessFluidVariablesEvent implements ControllerActionEventInterface
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var array
     */
    protected $settings = [];

    /**
     * @var array
     */
    protected $fluidVariables = [];

    public function __construct(
        Request $request,
        array $settings,
        array $fluidVariables
    ) {
        $this->request = $request;
        $this->settings = $settings;
        $this->fluidVariables = $fluidVariables;
    }

    public function getRequest(): Request
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
