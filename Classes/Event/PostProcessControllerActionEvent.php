<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/yellowpages2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Yellowpages2\Event;

use JWeiland\Yellowpages2\Controller\CompanyController;
use JWeiland\Yellowpages2\Controller\MapController;
use JWeiland\Yellowpages2\Domain\Model\Company;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Request;

/**
 * Post process controller actions which does not assign any variables to view.
 * Often used by controller actions like "update" or "create" which redirects after success.
 */
class PostProcessControllerActionEvent implements ControllerActionEventInterface
{
    /**
     * @var ActionController|CompanyController|MapController
     */
    protected $controller;

    /**
     * @var Company|null
     */
    protected $company;

    /**
     * @var array
     */
    protected $settings;

    public function __construct(
        ActionController $controller,
        ?Company $company,
        array $settings
    ) {
        $this->controller = $controller;
        $this->company = $company;
        $this->settings = $settings;
    }

    public function getController(): ActionController
    {
        return $this->controller;
    }

    public function getCompanyController(): CompanyController
    {
        return $this->controller;
    }

    public function getMapController(): MapController
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

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }
}
