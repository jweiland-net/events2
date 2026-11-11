<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Tca;

use JWeiland\Events2\Tca\TimeLabel;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for TimeLabel
 */
class TimeLabelTest extends FunctionalTestCase
{
    protected TimeLabel $subject;

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

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/TimeLabel.csv');

        $this->subject = new TimeLabel();
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
        );

        parent::tearDown();
    }

    /**
     * Reduced rows as built by BackendUtility::getCommonSelectFields()
     * without label_alt, e.g. for items of the core generated l10n_parent
     * select field in FormEngine.
     */
    public static function reducedRowDataProvider(): array
    {
        $systemFields = [
            't3ver_state' => 0,
            't3ver_wsid' => 0,
            'hidden' => 0,
            'starttime' => 0,
            'endtime' => 0,
        ];

        return [
            'event_time without type and time_end' => [
                ['uid' => 31, 'pid' => 37, 'time_begin' => '08:00'] + $systemFields,
                '08:00 - 10:00',
            ],
            'different_times without type and weekday' => [
                ['uid' => 32, 'pid' => 37, 'time_begin' => '14:00'] + $systemFields,
                '14:00: Wednesday',
            ],
            'event_time with empty time_begin as in reported stack trace' => [
                ['uid' => 33, 'pid' => 37, 'time_begin' => ''] + $systemFields,
                '',
            ],
            'different_times with empty time_begin' => [
                ['uid' => 32, 'pid' => 37, 'time_begin' => ''] + $systemFields,
                ': Wednesday',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    #[Test]
    #[DataProvider('reducedRowDataProvider')]
    public function getTitleWithReducedRowLoadsMissingFields(array $row, string $expectedTitle): void
    {
        $ctrlArray = $this->buildCtrlArray($row);

        $this->subject->getTitle($ctrlArray, null);

        self::assertSame($expectedTitle, $ctrlArray['title']);
    }

    #[Test]
    public function getTitleWithReducedRowOfUnknownRecordWillNotFail(): void
    {
        $ctrlArray = $this->buildCtrlArray(['uid' => 999, 'pid' => 37, 'time_begin' => '09:00']);

        $this->subject->getTitle($ctrlArray, null);

        self::assertSame('09:00', $ctrlArray['title']);
    }

    #[Test]
    public function getTitleWithReducedRowWithoutUidWillNotFail(): void
    {
        $ctrlArray = $this->buildCtrlArray(['time_begin' => '09:00']);

        $this->subject->getTitle($ctrlArray, null);

        self::assertSame('09:00', $ctrlArray['title']);
    }

    #[Test]
    public function getTitleWithFormEngineRowUsesGivenValues(): void
    {
        // In FormEngine weekday is processed into an array and record may be new
        $ctrlArray = $this->buildCtrlArray([
            'uid' => 'NEW1234',
            'type' => 'different_times',
            'weekday' => ['friday'],
            'time_begin' => '18:00',
            'time_end' => '20:00',
        ]);

        $this->subject->getTitle($ctrlArray, null);

        self::assertSame('18:00 - 20:00: Friday', $ctrlArray['title']);
    }

    #[Test]
    public function getTitleWithGivenValuesWillNotBeOverriddenByDatabase(): void
    {
        $ctrlArray = $this->buildCtrlArray([
            'uid' => 32,
            'type' => 'multiple_times',
            'weekday' => 'monday',
            'time_begin' => '15:00',
            'time_end' => '',
        ]);

        $this->subject->getTitle($ctrlArray, null);

        self::assertSame('15:00', $ctrlArray['title']);
    }

    #[Test]
    public function getTitleWithForeignTableWillNotModifyTitle(): void
    {
        $ctrlArray = $this->buildCtrlArray(['uid' => 32, 'time_begin' => '14:00']);
        $ctrlArray['table'] = 'tx_events2_domain_model_event';

        $this->subject->getTitle($ctrlArray, null);

        self::assertSame('', $ctrlArray['title']);
    }

    #[Test]
    public function commonSelectFieldsContainFieldsRequiredForLabel(): void
    {
        $fields = GeneralUtility::trimExplode(
            ',',
            BackendUtility::getCommonSelectFields('tx_events2_domain_model_time'),
            true,
        );

        foreach (['time_begin', 'time_end', 'type', 'weekday'] as $field) {
            self::assertContains($field, $fields);
        }
    }

    #[Test]
    public function getRecordTitleWithCommonSelectFieldsContainsWeekday(): void
    {
        $row = BackendUtility::getRecord(
            'tx_events2_domain_model_time',
            32,
            BackendUtility::getCommonSelectFields('tx_events2_domain_model_time'),
        );

        self::assertSame(
            '14:00: Wednesday',
            BackendUtility::getRecordTitle('tx_events2_domain_model_time', $row),
        );
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function buildCtrlArray(array $row): array
    {
        return [
            'table' => 'tx_events2_domain_model_time',
            'row' => $row,
            'title' => '',
            'options' => [],
        ];
    }
}
