<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\EventListener;

use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Event\PreProcessControllerActionEvent;
use JWeiland\Events2\EventListener\RegisterHiddenEventEventListener;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use JWeiland\Events2\Tests\Functional\Traits\InsertEventTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Mvc\Controller\Arguments;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Persistence\Generic\Session;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Events created in frontend are hidden until a moderator activates them.
 * The listener registers them in the Extbase session, so the "event"
 * argument of these actions can be mapped.
 */
class RegisterHiddenEventEventListenerTest extends FunctionalTestCase
{
    use InsertEventTrait;

    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'sjbr/static-info-tables',
        'jweiland/events2',
    ];

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'phpTimeZone' => Events2Constants::PHP_TIMEZONE,
        ],
    ];

    public static function actionDataProvider(): array
    {
        return [
            'perform action' => ['perform', true],
            'confirmActivation action' => ['confirmActivation', true],
            'listMyEvents action' => ['listMyEvents', false],
        ];
    }

    #[Test]
    #[DataProvider('actionDataProvider')]
    public function invokeRegistersHiddenEventForActions(string $actionName, bool $expectedRegistration): void
    {
        $eventUid = $this->insertEvent(
            'Hidden event',
            new \DateTimeImmutable('tomorrow midnight'),
            additionalFields: ['hidden' => 1],
        );

        $this->get(RegisterHiddenEventEventListener::class)(
            new PreProcessControllerActionEvent(
                $this->buildRequest($actionName, $eventUid),
                new Arguments(),
                [],
            ),
        );

        self::assertSame(
            $expectedRegistration,
            $this->get(Session::class)->hasIdentifier((string)$eventUid, Event::class),
        );
    }

    private function buildRequest(string $actionName, int $eventUid): Request
    {
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $extbaseRequestParameters->setControllerExtensionName('Events2');
        $extbaseRequestParameters->setPluginName('Management');
        $extbaseRequestParameters->setControllerName('Management');
        $extbaseRequestParameters->setControllerActionName($actionName);
        $extbaseRequestParameters->setArgument('event', $eventUid);

        return new Request(
            (new ServerRequest('https://www.example.com/', 'GET'))
                ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
                ->withAttribute('extbase', $extbaseRequestParameters),
        );
    }
}
