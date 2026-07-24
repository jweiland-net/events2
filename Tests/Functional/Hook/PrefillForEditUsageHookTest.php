<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Hook;

use JWeiland\Events2\Hook\Form\PrefillForEditUsageHook;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use JWeiland\Events2\Tests\Functional\Traits\InsertEventTrait;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for PrefillForEditUsageHook
 */
class PrefillForEditUsageHookTest extends FunctionalTestCase
{
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

    protected PrefillForEditUsageHook $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Events2PageTree.csv');

        $this->subject = new PrefillForEditUsageHook(
            $this->get(PageRepository::class),
        );
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
