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
        'form',
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
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
        unset($GLOBALS['TYPO3_REQUEST']);

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
        $eventUid = $this->insertEventOfOrganizer();
        $this->loginFrontendUser(1);

        self::assertTrue($this->isAccessAllowed($eventUid));
    }

    #[Test]
    public function isAccessAllowedForForeignEventReturnsFalse(): void
    {
        $eventUid = $this->insertEventOfOrganizer();
        $this->loginFrontendUser(2);

        self::assertFalse($this->isAccessAllowed($eventUid));
    }

    #[Test]
    public function isAccessAllowedForExistingEventWithBackendAdminReturnsTrue(): void
    {
        $eventUid = $this->insertEventOfOrganizer();
        $this->loginBackendUser(true);

        self::assertTrue($this->isAccessAllowed($eventUid));
    }

    /**
     * The finisher stores the event of the query, so the event of the
     * query has to be checked. An own event in the POST body must not
     * allow to edit a foreign event of the query.
     */
    #[Test]
    public function isAccessAllowedForForeignEventInQueryAndOwnEventInBodyReturnsFalse(): void
    {
        $ownEventUid = $this->insertEventOfOrganizer();
        $foreignEventUid = $this->insertEvent(
            title: 'Event of other organizer',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            organizer: 'TYPO3',
        );
        $this->loginFrontendUser(1);

        $this->setFinisherContext(
            $this->buildRequest($foreignEventUid)
                ->withParsedBody(['tx_events2_management' => ['event' => (string)$ownEventUid]]),
        );

        self::assertFalse($this->callThrowExceptionIfAccessIsDenied());
    }

    /**
     * A backend administrator has no organizer. Editing an event must not
     * remove the organizer (the owner) of the event.
     */
    #[Test]
    public function saveDataForOrganizersKeepsOrganizersOfEditedEvent(): void
    {
        $eventUid = $this->insertEventOfOrganizer();
        $this->setFinisherContext($this->buildRequest($eventUid));

        (new \ReflectionMethod($this->subject, 'saveDataForOrganizers'))->invoke(
            $this->subject,
            ['uid_local' => $eventUid, 'organizers' => ''],
            'tx_events2_event_organizer_mm',
            6,
        );

        self::assertSame(
            1,
            $this->getConnectionPool()
                ->getConnectionForTable('tx_events2_event_organizer_mm')
                ->count('*', 'tx_events2_event_organizer_mm', ['uid_local' => $eventUid]),
        );
    }

    private function isAccessAllowed(int $eventUid = 0): bool
    {
        $this->setFinisherContext($this->buildRequest($eventUid));

        return $this->callThrowExceptionIfAccessIsDenied();
    }

    private function callThrowExceptionIfAccessIsDenied(): bool
    {
        try {
            (new \ReflectionMethod($this->subject, 'throwExceptionIfAccessIsDenied'))->invoke($this->subject);
        } catch (FinisherException) {
            return false;
        }

        return true;
    }

    private function setFinisherContext(Request $request): void
    {
        (new \ReflectionProperty($this->subject, 'finisherContext'))->setValue(
            $this->subject,
            new FinisherContext(self::createStub(FormRuntime::class), $request),
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

    private function buildRequest(int $eventUid = 0): Request
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
                ->withQueryParams($eventUid > 0 ? ['tx_events2_management' => ['event' => (string)$eventUid]] : [])
                ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
                ->withAttribute('frontend.typoscript', $frontendTypoScript)
                ->withAttribute('extbase', $extbaseRequestParameters),
        );
    }
}
