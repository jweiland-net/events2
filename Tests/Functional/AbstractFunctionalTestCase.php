<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional;

use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Authentication\Mfa\MfaRequiredException;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Error\Http\InternalServerErrorException;
use TYPO3\CMS\Core\Error\Http\ServiceUnavailableException;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\EnvironmentService;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Abstract FunctionalTestCase. Contains methods to start up the TSFE
 */
class AbstractFunctionalTestCase extends FunctionalTestCase
{
    /**
     * It just starts up the TSFE
     * It's up to YOU to initialize DB before using this method:
     * parent::setUp();
     * $this->importDataSet('ntf://Database/pages.xml');
     * $this->setUpFrontendRootPage(1, [__DIR__ . '/../Fixtures/TypoScript/setup.typoscript']);
     *
     * @throws InternalServerErrorException
     * @throws MfaRequiredException
     * @throws NoSuchCacheException
     * @throws ServiceUnavailableException
     * @throws SiteNotFoundException
     */
    protected function startUpTSFE(ServerRequest $serverRequest, int $pageUid = 1, string $pageType = '0', array $arguments = []): void
    {
        $this->initializeLanguageService();
        $site = $this->getSite($pageUid);
        $context = $this->createContext($site);

        $pageArguments = GeneralUtility::makeInstance(
            PageArguments::class,
            $pageUid,
            $pageType,
            $arguments
        );
        $serverRequest = $serverRequest->withAttribute('routing', $pageArguments);

        $frontendUser = $this->createFrontendUser();
        $serverRequest = $serverRequest->withAttribute('frontend.user', $frontendUser);

        $controller = GeneralUtility::makeInstance(
            TypoScriptFrontendController::class,
            $context,
            $site,
            $site->getDefaultLanguage(),
            $pageArguments,
            $frontendUser
        );
        $controller->no_cache = true; // Do not cache in case of testing
        $controller->determineId($serverRequest);
        $controller->getFromCache($serverRequest);
        $controller->getConfigArray($serverRequest);

        $serverRequest = $serverRequest->withAttribute('frontend.controller', $controller);

        $GLOBALS['TYPO3_REQUEST'] = $serverRequest;
        $GLOBALS['TSFE'] = $controller;
    }

    protected function createContext(Site $site): Context
    {
        // GM::makeInstance is needed to respect SingletonInterface
        $context = GeneralUtility::makeInstance(Context::class);
        $context->setAspect(
            'language',
            LanguageAspectFactory::createFromSiteLanguage($site->getDefaultLanguage())
        );

        return $context;
    }

    /**
     * @return ObjectProphecy|FrontendUserAuthentication
     */
    protected function createFrontendUser(bool $loggedIn = true, array $groupIds = [1])
    {
        /** @var FrontendUserAuthentication|ObjectProphecy $frontendUserProphecy */
        $frontendUserProphecy = $this->prophesize(FrontendUserAuthentication::class);
        $frontendUserProphecy
            ->createUserAspect(true)
            ->willReturn($this->createUserAspect($loggedIn, $groupIds));

        return $frontendUserProphecy->reveal();
    }

    /**
     * @return ObjectProphecy|UserAspect
     */
    protected function createUserAspect(bool $loggedIn = true, array $groupIds = [1])
    {
        /** @var UserAspect|ObjectProphecy $userAspect */
        $userAspect = $this->prophesize(UserAspect::class);
        $userAspect->isUserOrGroupSet()->willReturn(true);

        $userAspect->get('id')->willReturn(1);

        $userAspect->isLoggedIn()->willReturn($loggedIn);
        $userAspect->get('isLoggedIn')->willReturn($loggedIn);

        $userAspect->getGroupIds()->willReturn($groupIds);
        $userAspect->get('groupIds')->willReturn($groupIds);

        return $userAspect->reveal();
    }

    protected function initializeLanguageService(): void
    {
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);
    }

    protected function getServerRequestForFrontendMode(): ServerRequest
    {
        // Needed for TYPO3 10 compatibility
        $this->setEnvironmentToFrontendMode();

        $applicationType = SystemEnvironmentBuilder::REQUESTTYPE_FE;
        $serverRequest = new ServerRequest();

        return $serverRequest->withAttribute('applicationType', $applicationType);
    }

    protected function setEnvironmentToFrontendMode(): void
    {
        // This part is needed for TYPO3 10 compatibility
        /** @var EnvironmentService|ObjectProphecy $environmentServiceProphecy */
        $environmentServiceProphecy = $this->prophesize(EnvironmentService::class);
        $environmentServiceProphecy
            ->isEnvironmentInFrontendMode()
            ->willReturn(true);
        $environmentServiceProphecy
            ->isEnvironmentInBackendMode()
            ->willReturn(false);
        GeneralUtility::setSingletonInstance(EnvironmentService::class, $environmentServiceProphecy->reveal());
    }

    /**
     * @throws SiteNotFoundException
     */
    protected function getSite(int $pageUid = 1): Site
    {
        return GeneralUtility::makeInstance(SiteFinder::class)->getSiteByRootPageId($pageUid);
    }
}
