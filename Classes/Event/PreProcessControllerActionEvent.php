<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/yellowpages2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Yellowpages2\Event;

use TYPO3\CMS\Extbase\Mvc\Controller\Arguments;
use TYPO3\CMS\Extbase\Mvc\Request;

class PreProcessControllerActionEvent implements ControllerActionEventInterface
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Arguments
     */
    protected $arguments;

    /**
     * @var array
     */
    protected $settings = [];

    public function __construct(
        Request $request,
        Arguments $arguments,
        array $settings
    ) {
        $this->request = $request;
        $this->arguments = $arguments;
        $this->settings = $settings;
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

    public function getArguments(): Arguments
    {
        return $this->arguments;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }
}
