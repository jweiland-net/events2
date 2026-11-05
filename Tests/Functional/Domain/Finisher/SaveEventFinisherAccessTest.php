<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Domain\Finisher;

use JWeiland\Events2\Domain\Finisher\SaveEventFinisher;
use JWeiland\Events2\Helper\PathSegmentHelper;
use JWeiland\Events2\Service\DayRelationService;
use JWeiland\Events2\Service\ManagementAccessService;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use JWeiland\Events2\Tests\Functional\Traits\FrontendUserTrait;
use JWeiland\Events2\Tests\Functional\Traits\InsertEventTrait;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException;
use TYPO3\CMS\Form\Domain\Finishers\FinisherContext;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The SaveEventFinisher stores events for allowed frontend users only,
 * even if the form is used outside the ManagementController.
 */
class SaveEventFinisherAccessTest extends FunctionalTestCase
{
    use FrontendUserTrait;
    use InsertEventTrait;

    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'form',
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

    private SaveEventFinisher $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new SaveEventFinisher(
            $this->get(ConnectionPool::class),
            $this->get(PathSegmentHelper::class),
            $this->get(DayRelationService::class),
            managementAccessService: $this->get(ManagementAccessService::class),
        );
    }

    protected function tearDown(): void
    {
        unset($_GET['tx_events2_management'], $GLOBALS['TYPO3_REQUEST']);

        parent::tearDown();
    }

    #[Test]
    public function executeWithoutLoggedInUserThrowsExceptionBeforeStoring(): void
    {
        $this->subject->setOptions([
            'table' => 'tx_events2_domain_model_event',
            'mode' => 'insert',
            'databaseColumnMappings' => [
                'title' => ['value' => 'Event of anonymous visitor'],
            ],
        ]);

        $this->expectException(FinisherException::class);
        $this->expectExceptionCode(1762950001);

        $this->subject->execute(new FinisherContext(self::createStub(FormRuntime::class), $this->buildRequest()));
    }

    #[Test]
    public function isAccessAllowedForNewEventWithoutLoggedInUserReturnsFalse(): void
    {
        self::assertFalse($this->isAccessAllowed());
    }

    #[Test]
    public function isAccessAllowedForNewEventWithUserOfOtherGroupReturnsFalse(): void
    {
        $this->loginFrontendUser(1, [2]);

        self::assertFalse($this->isAccessAllowed());
    }

    #[Test]
    public function isAccessAllowedForNewEventWithUserWithoutOrganizerReturnsFalse(): void
    {
        $this->loginFrontendUser(0);

        self::assertFalse($this->isAccessAllowed());
    }

    #[Test]
    public function isAccessAllowedForNewEventWithUserOfGroupReturnsTrue(): void
    {
        $this->loginFrontendUser(1);

        self::assertTrue($this->isAccessAllowed());
    }

    #[Test]
    public function isAccessAllowedForNewEventWithBackendAdminReturnsFalse(): void
    {
        $this->loginBackendUser(true);

        self::assertFalse($this->isAccessAllowed());
    }

    #[Test]
    public function isAccessAllowedForOwnEventReturnsTrue(): void
    {
        $_GET['tx_events2_management']['event'] = (string)$this->insertEventOfOrganizer();
        $this->loginFrontendUser(1);

        self::assertTrue($this->isAccessAllowed());
    }

    #[Test]
    public function isAccessAllowedForForeignEventReturnsFalse(): void
    {
        $_GET['tx_events2_management']['event'] = (string)$this->insertEventOfOrganizer();
        $this->loginFrontendUser(2);

        self::assertFalse($this->isAccessAllowed());
    }

    #[Test]
    public function isAccessAllowedForExistingEventWithBackendAdminReturnsTrue(): void
    {
        $_GET['tx_events2_management']['event'] = (string)$this->insertEventOfOrganizer();
        $this->loginBackendUser(true);

        self::assertTrue($this->isAccessAllowed());
    }

    private function isAccessAllowed(): bool
    {
        return (new \ReflectionMethod($this->subject, 'isAccessAllowed'))->invoke(
            $this->subject,
            $this->buildRequest(),
        );
    }

    private function insertEventOfOrganizer(): int
    {
        // The first organizer gets UID 1
        return $this->insertEvent(
            title: 'Hidden event of organizer',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            additionalFields: ['hidden' => 1],
            organizer: 'jweiland.net',
        );
    }

    private function buildRequest(): Request
    {
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $extbaseRequestParameters->setControllerExtensionName('Events2');
        $extbaseRequestParameters->setPluginName('Management');
        $extbaseRequestParameters->setControllerName('Management');
        $extbaseRequestParameters->setControllerActionName('perform');

        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([
            'plugin.' => [
                'tx_events2.' => [
                    'settings.' => [
                        'userGroup' => '1',
                    ],
                ],
            ],
        ]);

        return new Request(
            (new ServerRequest('https://www.example.com/', 'POST'))
                ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
                ->withAttribute('frontend.typoscript', $frontendTypoScript)
                ->withAttribute('extbase', $extbaseRequestParameters),
        );
    }
}
