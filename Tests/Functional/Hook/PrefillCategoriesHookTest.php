<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Hook;

use JWeiland\Events2\Hook\Form\PrefillCategoriesHook;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for PrefillCategoriesHook
 */
class PrefillCategoriesHookTest extends FunctionalTestCase
{
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

    protected function setUp(): void
    {
        parent::setUp();

        // Cars (1) is the parent of Audi (2) and BMW (3)
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Events2PageTree.csv');
    }

    public static function settingsDataProvider(): array
    {
        return [
            'setting missing, root category set' => [
                ['rootCategory' => '1'],
                [2 => 'Audi', 3 => 'BMW'],
            ],
            'setting missing, "new" missing as well' => [
                ['rootCategory' => '1', 'new' => []],
                [2 => 'Audi', 3 => 'BMW'],
            ],
            'setting empty, root category set' => [
                ['rootCategory' => '1', 'new' => ['selectableCategoriesForNewEvents' => '']],
                [2 => 'Audi', 3 => 'BMW'],
            ],
            'setting null, root category set' => [
                ['rootCategory' => '1', 'new' => ['selectableCategoriesForNewEvents' => null]],
                [2 => 'Audi', 3 => 'BMW'],
            ],
            'setting and root category missing' => [
                [],
                [],
            ],
            'setting empty, root category empty' => [
                ['rootCategory' => '', 'new' => ['selectableCategoriesForNewEvents' => '']],
                [],
            ],
            'setting with list of category UIDs' => [
                ['rootCategory' => '1', 'new' => ['selectableCategoriesForNewEvents' => '3, 2']],
                [2 => 'Audi', 3 => 'BMW'],
            ],
            'setting with one category UID as string' => [
                ['rootCategory' => '1', 'new' => ['selectableCategoriesForNewEvents' => '3']],
                [3 => 'BMW'],
            ],
            'setting with one category UID as int' => [
                ['rootCategory' => 1, 'new' => ['selectableCategoriesForNewEvents' => 2]],
                [2 => 'Audi'],
            ],
            'setting with category which is no child of root category' => [
                ['rootCategory' => '1', 'new' => ['selectableCategoriesForNewEvents' => '1,3']],
                [3 => 'BMW'],
            ],
            'setting without root category allows top level categories only' => [
                ['new' => ['selectableCategoriesForNewEvents' => '1,2']],
                [1 => 'Cars'],
            ],
            'setting with invalid values only falls back to root category' => [
                ['rootCategory' => '1', 'new' => ['selectableCategoriesForNewEvents' => 'foo,0']],
                [2 => 'Audi', 3 => 'BMW'],
            ],
        ];
    }

    #[Test]
    #[DataProvider('settingsDataProvider')]
    public function initializeFormElementSetsCategoriesAsOptions(array $settings, array $expectedOptions): void
    {
        $formElement = $this->buildFormElement('categories');

        $this->buildSubject($settings)->initializeFormElement($formElement);

        self::assertSame(
            $expectedOptions,
            $formElement->getProperties()['options'],
        );
    }

    #[Test]
    public function initializeFormElementWithOtherFormElementDoesNotSetOptions(): void
    {
        $formElement = $this->buildFormElement('weekday');

        $this->buildSubject(['rootCategory' => '1'])->initializeFormElement($formElement);

        self::assertArrayNotHasKey(
            'options',
            $formElement->getProperties(),
        );
    }

    private function buildSubject(array $settings): PrefillCategoriesHook
    {
        $configurationManagerMock = $this->createMock(ConfigurationManagerInterface::class);
        $configurationManagerMock
            ->method('getConfiguration')
            ->with(ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS, 'Events2', 'Management')
            ->willReturn($settings);

        return new PrefillCategoriesHook(
            $this->get(PageRepository::class),
            $configurationManagerMock,
            $this->get(ConnectionPool::class),
        );
    }

    private function buildFormElement(string $identifier): GenericFormElement
    {
        $formElement = new GenericFormElement($identifier, 'MultiCheckbox');
        $formElement->setParentRenderable(new FormDefinition('newEvent'));

        return $formElement;
    }
}
