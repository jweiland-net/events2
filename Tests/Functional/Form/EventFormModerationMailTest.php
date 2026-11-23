<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Form;

use JWeiland\Events2\Service\ManagementAccessService;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use JWeiland\Events2\Tests\Functional\Traits\CacheHashTrait;
use JWeiland\Events2\Tests\Functional\Traits\CreatePostStreamBodyTrait;
use JWeiland\Events2\Tests\Functional\Traits\SiteBasedTestTrait;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Form\Domain\Runtime\FormState;
use TYPO3\CMS\Form\Security\HashScope;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Submits the event form in frontend with a logged-in organizer and
 * follows the activation link of the moderation mail. The mail is written
 * into a mbox file.
 */
class EventFormModerationMailTest extends FunctionalTestCase
{
    use CacheHashTrait;
    use CreatePostStreamBodyTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => [
            'id' => 0,
            'title' => 'English',
            'locale' => 'en_US.UTF8',
            'iso' => 'en',
        ],
    ];

    private const PLUGIN_NAMESPACE = 'tx_events2_management';

    private const MBOX_FILE = '/tmp/events2-functional-test.mbox';

    private const FRONTEND_USER = 1;

    protected array $coreExtensionsToLoad = [
        'fluid_styled_content',
        'form',
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'jweiland/events2',
        __DIR__ . '/../Fixtures/Extensions/site_package',
    ];

    protected array $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'events2' => [
                'emailFromAddress' => 'sender@example.com',
                'emailFromName' => 'Sender',
                'emailToAddress' => 'moderator@example.com',
                'emailToName' => 'Moderator',
            ],
        ],
        'MAIL' => [
            'transport' => 'mbox',
            'transport_mbox_file' => self::MBOX_FILE,
            'defaultMailFromAddress' => 'noreply@example.com',
            'defaultMailFromName' => 'Events2',
        ],
        'SYS' => [
            'phpTimeZone' => Events2Constants::PHP_TIMEZONE,
        ],
    ];

    protected array $additionalFoldersToCreate = [
        'fileadmin/Extensions/events2',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        @unlink(self::MBOX_FILE);
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Events2PageTree.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/fe_users.csv');
        $this->getConnectionPool()->getConnectionForTable('tx_events2_domain_model_location')->insert(
            'tx_events2_domain_model_location',
            ['uid' => 1, 'pid' => Events2Constants::PAGE_STORAGE, 'location' => 'Town hall'],
        );

        $this->writeSiteConfiguration(
            'events2-moderation-mail-test',
            $this->buildSiteConfiguration(1, 'https://www.example.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ],
            [],
            ['jweiland/sitepackage'],
        );
    }

    protected function tearDown(): void
    {
        @unlink(self::MBOX_FILE);
        parent::tearDown();
    }

    #[Test]
    public function submitOfEventFormSendsMailWithLinksOfEvents2(): void
    {
        $this->submitEventForm();

        $event = $this->getNewEvent();
        $moderationToken = $this->get(ManagementAccessService::class)->generateModerationToken((int)$event['uid']);
        $mail = $this->getMail();

        self::assertSame(1, (int)$event['hidden']);
        self::assertNotSame('', $moderationToken);
        self::assertStringContainsString('To: Moderator <moderator@example.com>', $mail);
        self::assertStringContainsString(urlencode(self::PLUGIN_NAMESPACE . '[action]') . '=perform', $mail);
        self::assertStringContainsString(urlencode(self::PLUGIN_NAMESPACE . '[action]') . '=confirmActivation', $mail);
        self::assertStringContainsString(urlencode(self::PLUGIN_NAMESPACE . '[event]') . '=' . $event['uid'], $mail);
        self::assertStringContainsString(
            urlencode(self::PLUGIN_NAMESPACE . '[moderationToken]') . '=' . $moderationToken,
            $mail,
        );
        self::assertStringNotContainsString(urlencode(self::PLUGIN_NAMESPACE . '[moderationToken]') . '=&', $mail);
        self::assertStringNotContainsString('tx_yellowpages2', $mail);
        self::assertStringNotContainsString('tx_clubdirectory', $mail);
    }

    /**
     * Links in the text part are copied by the moderator. They must not
     * contain HTML entities like "&amp;", else the parameters are broken.
     */
    #[Test]
    public function textPartOfMailContainsLinksWithoutHtmlEntities(): void
    {
        $this->submitEventForm();

        $textPart = $this->getTextPart();

        self::assertMatchesRegularExpression(
            '#https://\\S+' . preg_quote(urlencode(self::PLUGIN_NAMESPACE . '[action]'), '#') . '=confirmActivation&\\S+#',
            $textPart,
        );
        self::assertMatchesRegularExpression(
            '#https://\\S+' . preg_quote(urlencode(self::PLUGIN_NAMESPACE . '[action]'), '#') . '=perform&\\S+#',
            $textPart,
        );
        foreach (['&amp;', '&quot;', '&#039;', '&lt;', '&gt;'] as $htmlEntity) {
            self::assertStringNotContainsString($htmlEntity, $textPart);
        }
    }

    #[Test]
    public function activationLinkOfMailShowsConfirmButtonAndActivatesEventWithMessage(): void
    {
        $this->submitEventForm();
        $event = $this->getNewEvent();
        $moderationToken = $this->get(ManagementAccessService::class)->generateModerationToken((int)$event['uid']);

        $content = (string)$this->request('confirmActivation', [
            'event' => (string)$event['uid'],
            'moderationToken' => $moderationToken,
        ])->getBody();

        self::assertStringContainsString('Concert in the town hall', $content);

        $this->request('activate', [], [
            self::PLUGIN_NAMESPACE => [
                'controller' => 'Management',
                'action' => 'activate',
                'event' => (string)$event['uid'],
                'moderationToken' => $moderationToken,
            ],
        ]);

        self::assertSame(0, (int)$this->getNewEvent()['hidden']);
        $sessionData = $this->getConnectionPool()
            ->getConnectionForTable('fe_sessions')
            ->select(['ses_data'], 'fe_sessions')
            ->fetchFirstColumn();
        self::assertStringContainsString('A record was activated', implode('', array_map(strval(...), $sessionData)));
    }

    /**
     * The value of the first page is part of the signed form state. The
     * second page is the last enabled page of a single event.
     */
    private function submitEventForm(): void
    {
        $formState = new FormState();
        $formState->setLastDisplayedPageIndex(1);
        $formState->setFormValue('choose-event-type', 'single');

        $response = $this->request('perform', [], [
            self::PLUGIN_NAMESPACE => [
                'newEvent' => [
                    '__state' => $this->get(HashService::class)->appendHmac(
                        base64_encode(serialize($formState)),
                        HashScope::FormState->prefix(),
                    ),
                    '__currentPage' => '2',
                    'title' => 'Concert in the town hall',
                    'event-begin' => (new \DateTimeImmutable('tomorrow'))->format('Y-m-d'),
                    'event-location' => '1',
                    'categories' => ['2'],
                ],
            ],
        ], self::FRONTEND_USER);

        self::assertLessThan(500, $response->getStatusCode());
    }

    /**
     * @param array<string, string> $pluginArguments Arguments of the query string
     * @param array<string, mixed> $parsedBody
     */
    private function request(
        string $actionName,
        array $pluginArguments = [],
        array $parsedBody = [],
        int $frontendUserUid = 0,
    ): ResponseInterface {
        $parameters = [
            self::PLUGIN_NAMESPACE => ['controller' => 'Management', 'action' => $actionName] + $pluginArguments,
        ];
        $parameters['cHash'] = $this->generateCacheHash($parameters, Events2Constants::PAGE_MANAGEMENT);

        $request = (new InternalRequest('https://www.example.com/'))
            ->withPageId(Events2Constants::PAGE_MANAGEMENT)
            ->withQueryParams($parameters);
        if ($parsedBody !== []) {
            $request = $request
                ->withMethod('POST')
                ->withAddedHeader('Content-type', 'application/x-www-form-urlencoded')
                ->withBody($this->createBodyFromArray($parsedBody));
        }

        return $this->executeFrontendSubRequest(
            $request,
            (new InternalRequestContext())->withFrontendUserId($frontendUserUid),
        );
    }

    /**
     * Returns the decoded text/plain part of the sent mail
     */
    private function getTextPart(): string
    {
        self::assertFileExists(self::MBOX_FILE, 'No mail was sent');
        $mail = str_replace("\r\n", "\n", (string)file_get_contents(self::MBOX_FILE));

        $found = preg_match('#\\nContent-Type: text/plain[^\\n]*\\n((?:[^\\n]+\\n)*)\\n(.*?)\\n--#s', $mail, $matches);
        self::assertSame(1, $found, 'The mail has no text part');

        return str_contains(strtolower($matches[1]), 'quoted-printable') ? quoted_printable_decode($matches[2]) : $matches[2];
    }

    /**
     * Returns the decoded text and HTML part of the sent mail
     */
    private function getMail(): string
    {
        self::assertFileExists(self::MBOX_FILE, 'No mail was sent');

        return quoted_printable_decode((string)file_get_contents(self::MBOX_FILE));
    }

    /**
     * @return array<string, mixed>
     */
    private function getNewEvent(): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_event');
        $queryBuilder->getRestrictions()->removeAll();
        $event = $queryBuilder
            ->select('*')
            ->from('tx_events2_domain_model_event')
            ->where($queryBuilder->expr()->eq('title', $queryBuilder->createNamedParameter('Concert in the town hall')))
            ->executeQuery()
            ->fetchAssociative();
        self::assertIsArray($event, 'The event was not stored');

        return $event;
    }
}
