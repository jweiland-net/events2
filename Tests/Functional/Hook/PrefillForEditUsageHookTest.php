<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Hook;

use JWeiland\Events2\Converter\RteContentConverter;
use JWeiland\Events2\Domain\Factory\ArrayFormFactory;
use JWeiland\Events2\Hook\Form\PrefillForEditUsageHook;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use JWeiland\Events2\Tests\Functional\Traits\FrontendUserTrait;
use JWeiland\Events2\Tests\Functional\Traits\InsertEventTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for PrefillForEditUsageHook
 */
class PrefillForEditUsageHookTest extends FunctionalTestCase
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
        'EXTENSIONS' => [
            'events2' => [
                'emailFromAddress' => 'projects@jweiland.net',
                'emailFromName' => 'Stefan Froemken',
                'emailToAddress' => 'hosting@jweiland.net',
                'emailToName' => 'Stefan Froemken',
            ],
        ],
        'SYS' => [
            'phpTimeZone' => Events2Constants::PHP_TIMEZONE,
        ],
    ];

    protected PrefillForEditUsageHook $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Events2PageTree.csv');

        $this->subject = $this->get(PrefillForEditUsageHook::class);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);

        parent::tearDown();
    }

    #[Test]
    public function setFormDefaultValuesWithColumnAndRelationKeepsUidAsDefaultValueAndResolvesLabel(): void
    {
        $eventUid = $this->insertEvent(
            title: 'Event with location',
            eventBegin: new \DateTimeImmutable('today midnight'),
            location: 'Marketplace',
        );
        $eventRecord = $this->getEventRecord($eventUid);

        $formElement = $this->buildFormElement('location', [
            'dbMapping' => [
                'column' => 'location',
                'relation' => [
                    'table' => 'tx_events2_domain_model_location',
                    'labelColumn' => 'location',
                    'expressions' => [
                        [
                            'column' => 'uid',
                            'expression' => 'eq',
                            'value' => '{event:location}',
                        ],
                    ],
                ],
            ],
        ]);

        $this->callSetFormDefaultValues($formElement, $eventRecord);

        self::assertSame(
            $eventRecord['location'],
            $formElement->getDefaultValue(),
        );
        self::assertSame(
            'Marketplace',
            $formElement->getProperties()['resolvedLabel'],
        );
    }

    #[Test]
    public function setFormDefaultValuesWithoutRelationDoesNotSetResolvedLabel(): void
    {
        $eventUid = $this->insertEvent(
            title: 'Event without relation config',
            eventBegin: new \DateTimeImmutable('today midnight'),
            location: 'Marketplace',
        );
        $eventRecord = $this->getEventRecord($eventUid);

        $formElement = $this->buildFormElement('location', [
            'dbMapping' => [
                'column' => 'location',
            ],
        ]);

        $this->callSetFormDefaultValues($formElement, $eventRecord);

        self::assertSame(
            $eventRecord['location'],
            $formElement->getDefaultValue(),
        );
        self::assertArrayNotHasKey(
            'resolvedLabel',
            $formElement->getProperties(),
        );
    }

    #[Test]
    public function getLabelWithMissingRelationRecordReturnsEmptyString(): void
    {
        $eventUid = $this->insertEvent(
            title: 'Event without location',
            eventBegin: new \DateTimeImmutable('today midnight'),
        );
        $eventRecord = $this->getEventRecord($eventUid);

        $formElement = $this->buildFormElement('location', [
            'dbMapping' => [
                'column' => 'location',
                'relation' => [
                    'table' => 'tx_events2_domain_model_location',
                    'labelColumn' => 'location',
                    'expressions' => [
                        [
                            'column' => 'uid',
                            'expression' => 'eq',
                            'value' => '{event:location}',
                        ],
                    ],
                ],
            ],
        ]);

        $this->callSetFormDefaultValues($formElement, $eventRecord);

        self::assertSame(
            $eventRecord['location'],
            $formElement->getDefaultValue(),
        );
        self::assertSame(
            '',
            $formElement->getProperties()['resolvedLabel'],
        );
    }

    public static function storedDescriptionDataProvider(): array
    {
        return [
            'saved by frontend form (backend RTE format)' => [
                '<p>Erste Zeile der Beschreibung.<br />Zweite Zeile direkt darunter.</p>'
                . "\r\n"
                . '<p>Neuer Absatz nach einer Leerzeile.<br />Letzte Zeile mit Umlauten: äöü ß &amp; "Anführungszeichen".</p>',
            ],
            'saved by frontend form before 11.1.1' => [
                "<p>Erste Zeile der Beschreibung.<br />\nZweite Zeile direkt darunter.</p>"
                . "<p>Neuer Absatz nach einer Leerzeile.<br />\n"
                . 'Letzte Zeile mit Umlauten: äöü ß &amp; &quot;Anführungszeichen&quot;.</p>',
            ],
        ];
    }

    #[Test]
    #[DataProvider('storedDescriptionDataProvider')]
    public function setFormDefaultValuesWithPlainTextDataTypeReturnsOriginalPlainText(string $detailInformation): void
    {
        $eventUid = $this->insertEvent(
            title: 'Event with description',
            eventBegin: new \DateTimeImmutable('today midnight'),
            additionalFields: ['detail_information' => $detailInformation],
        );

        $formElement = $this->buildFormElement('detail-description', [
            'dbMapping' => [
                'column' => 'detail_information',
                'dataType' => 'plainText',
            ],
        ]);

        $this->callSetFormDefaultValues($formElement, $this->getEventRecord($eventUid));

        self::assertSame(
            "Erste Zeile der Beschreibung.\n"
            . "Zweite Zeile direkt darunter.\n"
            . "\n"
            . "Neuer Absatz nach einer Leerzeile.\n"
            . 'Letzte Zeile mit Umlauten: äöü ß & "Anführungszeichen".',
            $formElement->getDefaultValue(),
        );
    }

    #[Test]
    public function frontendDescriptionSurvivesRoundTripThroughDatabaseAndEditForm(): void
    {
        $plainText = "Erste Zeile.\r\nZweite Zeile & \"Zitat\".\r\n\r\nNeuer Absatz mit äöü ß.";
        $storedHtml = (new RteContentConverter())->convertPlainTextToHtml($plainText);
        $eventUid = $this->insertEvent(
            title: 'Event with description',
            eventBegin: new \DateTimeImmutable('today midnight'),
            additionalFields: ['detail_information' => $storedHtml],
        );

        $formElement = $this->buildFormElement('detail-description', [
            'dbMapping' => [
                'column' => 'detail_information',
                'dataType' => 'plainText',
            ],
        ]);
        $this->callSetFormDefaultValues($formElement, $this->getEventRecord($eventUid));

        self::assertSame(
            str_replace("\r\n", "\n", $plainText),
            $formElement->getDefaultValue(),
        );
        self::assertSame(
            $storedHtml,
            (new RteContentConverter())->convertPlainTextToHtml($formElement->getDefaultValue()),
        );
    }

    #[Test]
    public function setFormDefaultValuesKeepsInlineNonBreakingSpaceAndRemovesEmptyParagraph(): void
    {
        $eventUid = $this->insertEvent(
            title: 'Event with non-breaking spaces',
            eventBegin: new \DateTimeImmutable('today midnight'),
            additionalFields: [
                'detail_information' => "<p>Beginn 10&nbsp;Uhr</p>\r\n<p>&nbsp;</p>\r\n<p>Zweiter Absatz</p>",
            ],
        );

        $formElement = $this->buildFormElement('detail-description', [
            'dbMapping' => [
                'column' => 'detail_information',
                'dataType' => 'plainText',
            ],
        ]);
        $this->callSetFormDefaultValues($formElement, $this->getEventRecord($eventUid));

        self::assertSame(
            "Beginn 10\u{00A0}Uhr\n\nZweiter Absatz",
            $formElement->getDefaultValue(),
        );
    }

    #[Test]
    public function nonBreakingSpaceSurvivesRoundTripThroughDatabaseAndEditForm(): void
    {
        $storedHtml = (new RteContentConverter())->convertPlainTextToHtml("Beginn 10\u{00A0}Uhr");
        $eventUid = $this->insertEvent(
            title: 'Event with non-breaking space',
            eventBegin: new \DateTimeImmutable('today midnight'),
            additionalFields: ['detail_information' => $storedHtml],
        );

        $formElement = $this->buildFormElement('detail-description', [
            'dbMapping' => [
                'column' => 'detail_information',
                'dataType' => 'plainText',
            ],
        ]);
        $this->callSetFormDefaultValues($formElement, $this->getEventRecord($eventUid));

        self::assertSame('<p>Beginn 10&nbsp;Uhr</p>', $storedHtml);
        self::assertSame("Beginn 10\u{00A0}Uhr", $formElement->getDefaultValue());
        self::assertSame(
            $storedHtml,
            (new RteContentConverter())->convertPlainTextToHtml($formElement->getDefaultValue()),
        );
    }

    public static function storedTeaserDataProvider(): array
    {
        return [
            'teaser stored as HTML by events2 < 11.1.1' => [
                "<p>Kurz &amp; knapp<br />\nZweite Zeile</p>",
            ],
            'teaser stored as plain text' => [
                "Kurz & knapp\r\nZweite Zeile",
            ],
        ];
    }

    #[Test]
    #[DataProvider('storedTeaserDataProvider')]
    public function setFormDefaultValuesWithStoredTeaserReturnsPlainText(string $teaser): void
    {
        $eventUid = $this->insertEvent(
            title: 'Event with teaser',
            eventBegin: new \DateTimeImmutable('today midnight'),
            additionalFields: ['teaser' => $teaser],
        );

        $formElement = $this->buildFormElement('short-description', [
            'dbMapping' => [
                'column' => 'teaser',
                'dataType' => 'plainText',
            ],
        ]);
        $this->callSetFormDefaultValues($formElement, $this->getEventRecord($eventUid));

        self::assertSame(
            "Kurz & knapp\nZweite Zeile",
            $formElement->getDefaultValue(),
        );
    }

    #[Test]
    public function setFormDefaultValuesWithRichTextDataTypeReturnsStoredHtml(): void
    {
        $storedHtml = '<h2>Programm</h2>' . "\r\n" . '<p>10&nbsp;Uhr &amp; <a href="t3://page?uid=11">mehr</a></p>';
        $eventUid = $this->insertEvent(
            title: 'Event with rich text',
            eventBegin: new \DateTimeImmutable('today midnight'),
            additionalFields: ['detail_information' => $storedHtml],
        );

        $formElement = $this->buildFormElement('detail-description', [
            'dbMapping' => [
                'column' => 'detail_information',
                'dataType' => 'richText',
            ],
        ]);
        $this->callSetFormDefaultValues($formElement, $this->getEventRecord($eventUid));

        self::assertSame($storedHtml, $formElement->getDefaultValue());
    }

    #[Test]
    public function afterBuildingFinishedWithOwnEventPrefillsElement(): void
    {
        $eventUid = $this->insertEventOfOrganizer();
        $this->loginFrontendUser(1);
        $this->setRequestWithEvent($eventUid);

        $formElement = $this->buildFormElement('title', ['dbMapping' => ['column' => 'title']]);
        $this->subject->afterBuildingFinished($formElement);

        self::assertSame('Hidden event of organizer', $formElement->getDefaultValue());
    }

    /**
     * The form could be used outside the management plugin. Nobody may
     * read hidden events of other organizers via the query.
     */
    #[Test]
    public function afterBuildingFinishedWithForeignEventDoesNotPrefillElement(): void
    {
        $eventUid = $this->insertEventOfOrganizer();
        $this->loginFrontendUser(2);
        $this->setRequestWithEvent($eventUid);

        $formElement = $this->buildFormElement('title', ['dbMapping' => ['column' => 'title']]);
        $this->subject->afterBuildingFinished($formElement);

        self::assertNull($formElement->getDefaultValue());
    }

    #[Test]
    public function afterBuildingFinishedWithoutLoggedInUserDoesNotPrefillElement(): void
    {
        $eventUid = $this->insertEventOfOrganizer();
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest('https://www.example.com/');
        $this->setRequestWithEvent($eventUid);

        $formElement = $this->buildFormElement('title', ['dbMapping' => ['column' => 'title']]);
        $this->subject->afterBuildingFinished($formElement);

        self::assertNull($formElement->getDefaultValue());
    }

    /**
     * Builds the complete form of the edit view. All relations are
     * resolved by the expressions of NewEvent.form.yaml on every DBMS.
     */
    #[Test]
    public function buildNewEventFormPrefillsOwnEventInclusiveRelations(): void
    {
        $eventUid = $this->insertEventWithRelations();
        $this->loginFrontendUser(1);
        $this->setRequestWithEvent($eventUid);

        $this->get(ConfigurationManagerInterface::class)->setRequest($GLOBALS['TYPO3_REQUEST']);
        $formDefinition = $this->get(ArrayFormFactory::class)->build(
            Yaml::parseFile(GeneralUtility::getFileAbsFileName('EXT:events2/Configuration/Form/NewEvent.form.yaml')),
            'standard',
            $GLOBALS['TYPO3_REQUEST'],
        );

        self::assertSame('Event with relations', $formDefinition->getElementByIdentifier('title')->getDefaultValue());
        self::assertSame('19:00', $formDefinition->getElementByIdentifier('time-begin')->getDefaultValue());
        self::assertSame(
            'https://tickets.example.com',
            $formDefinition->getElementByIdentifier('ticket-link')->getDefaultValue(),
        );
        self::assertSame(
            'https://download.example.com',
            $formDefinition->getElementByIdentifier('download-link')->getDefaultValue(),
        );
        self::assertSame('https://youtu.be/abc', $formDefinition->getElementByIdentifier('youtube-link')->getDefaultValue());
        self::assertSame('Kino', $formDefinition->getElementByIdentifier('event-location')->getProperties()['resolvedLabel']);
        // The order of the categories depends on the database platform
        self::assertEqualsCanonicalizing([3, 5], $formDefinition->getElementByIdentifier('categories')->getDefaultValue());
        self::assertSame(
            $eventUid,
            $formDefinition->getRenderingOptions()['additionalParams']['tx_events2_management']['event'],
        );
    }

    private function insertEventWithRelations(): int
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('tx_events2_domain_model_link');
        $connection->insert('tx_events2_domain_model_link', [
            'pid' => Events2Constants::PAGE_STORAGE,
            'title' => 'Download',
            'link' => 'https://download.example.com',
        ]);
        $downloadLinkUid = (int)$connection->lastInsertId();

        // The first organizer gets UID 1, which is the organizer of loginFrontendUser(1)
        return $this->insertEvent(
            title: 'Event with relations',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            timeBegin: '19:00',
            ticketLink: 'https://tickets.example.com',
            videoLink: 'https://youtu.be/abc',
            additionalFields: ['hidden' => 1, 'download_links' => (string)$downloadLinkUid],
            organizer: 'jweiland.net',
            location: 'Kino',
            categories: [3, 5],
        );
    }

    private function insertEventOfOrganizer(): int
    {
        // The first organizer gets UID 1, which is the organizer of loginFrontendUser(1)
        return $this->insertEvent(
            title: 'Hidden event of organizer',
            eventBegin: new \DateTimeImmutable('tomorrow midnight'),
            additionalFields: ['hidden' => 1],
            organizer: 'jweiland.net',
        );
    }

    /**
     * Adds the event to edit and the user group to the global request of loginFrontendUser()
     */
    private function setRequestWithEvent(int $eventUid): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([
            'plugin.' => [
                'tx_events2.' => ['settings.' => [
                    'userGroup' => '1',
                    'new.' => ['selectableCategoriesForNewEvents' => '3,5'],
                ]],
                'tx_form.' => ['settings.' => ['yamlConfigurations.' => [
                    '10' => 'EXT:form/Configuration/Yaml/FormSetup.yaml',
                    '1661247670' => 'EXT:events2/Configuration/Form/OverrideFormSettings.yaml',
                ]]],
            ],
        ]);

        $GLOBALS['TYPO3_REQUEST'] = $GLOBALS['TYPO3_REQUEST']
            ->withQueryParams(['tx_events2_management' => ['event' => (string)$eventUid]])
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
    }

    private function buildFormElement(string $identifier, array $properties): GenericFormElement
    {
        $formDefinition = new FormDefinition('test-form');
        $formElement = new GenericFormElement($identifier, 'Events2Location');
        $formElement->setParentRenderable($formDefinition);

        foreach ($properties as $key => $value) {
            $formElement->setProperty($key, $value);
        }

        return $formElement;
    }

    private function callSetFormDefaultValues(GenericFormElement $formElement, array $eventRecord): void
    {
        $method = new \ReflectionMethod($this->subject, 'setFormDefaultValues');
        $method->invoke($this->subject, $formElement, $eventRecord);
    }

    private function getEventRecord(int $eventUid): array
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('tx_events2_domain_model_event');

        return $connection->select(['*'], 'tx_events2_domain_model_event', ['uid' => $eventUid])
            ->fetchAssociative();
    }
}
