<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Traits;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Logs in a frontend or backend user without a frontend request.
 * UserRepository reads the frontend user of the global TYPO3 request.
 */
trait FrontendUserTrait
{
    /**
     * @param int[] $groupIds
     */
    protected function loginFrontendUser(int $organizerUid, array $groupIds = [1]): void
    {
        $frontendUser = new FrontendUserAuthentication();
        $frontendUser->user = [
            'uid' => 1,
            'usergroup' => implode(',', $groupIds),
            'tx_events2_organizer' => $organizerUid,
        ];

        $this->get(Context::class)->setAspect(
            'frontend.user',
            new UserAspect($frontendUser, array_merge([0, -2], $groupIds)),
        );

        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://www.example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.user', $frontendUser);
    }

    protected function loginBackendUser(bool $isAdmin): void
    {
        $backendUser = new BackendUserAuthentication();
        $backendUser->user = [
            'uid' => 1,
            'admin' => (int)$isAdmin,
        ];

        $this->get(Context::class)->setAspect('backend.user', new UserAspect($backendUser));
    }
}
