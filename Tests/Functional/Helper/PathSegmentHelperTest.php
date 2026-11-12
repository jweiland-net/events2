<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Helper;

use JWeiland\Events2\Helper\PathSegmentHelper;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test
 */
class PathSegmentHelperTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
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

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/PathSegmentHelper.csv');
    }

    #[Test]
    public function generatePathSegmentForNewRecordReturnsSlugOfTitle(): void
    {
        self::assertSame(
            'weekly-market',
            $this->get(PathSegmentHelper::class)->generatePathSegment([
                'pid' => Events2Constants::PAGE_STORAGE,
                'title' => 'Weekly market',
            ]),
        );
    }

    #[Test]
    public function generatePathSegmentForNewRecordWithExistingSlugAppendsIncrement(): void
    {
        self::assertSame(
            'weekmarket-1',
            $this->get(PathSegmentHelper::class)->generatePathSegment([
                'pid' => Events2Constants::PAGE_STORAGE,
                'title' => 'Weekmarket',
            ]),
        );
    }

    #[Test]
    public function generatePathSegmentForStoredRecordIgnoresOwnSlug(): void
    {
        self::assertSame(
            'weekmarket',
            $this->get(PathSegmentHelper::class)->generatePathSegment([
                'uid' => 1,
                'pid' => Events2Constants::PAGE_STORAGE,
                'title' => 'Weekmarket',
            ]),
        );
    }

    #[Test]
    public function generatePathSegmentForRecordOfOtherLanguageIgnoresSlugOfDefaultLanguage(): void
    {
        self::assertSame(
            'weekmarket',
            $this->get(PathSegmentHelper::class)->generatePathSegment([
                'pid' => Events2Constants::PAGE_STORAGE,
                'sys_language_uid' => 1,
                'title' => 'Weekmarket',
            ]),
        );
    }

    #[Test]
    public function generatePathSegmentAppliesPostModifiersOfTca(): void
    {
        $slugConfiguration = &$GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['path_segment']['config'];
        $slugConfiguration['generatorOptions']['postModifiers'][] = static fn(array $parameters): string
            => $parameters['slug'] . '-modified';
        unset($slugConfiguration);
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        self::assertSame(
            'weekly-market-modified',
            $this->get(PathSegmentHelper::class)->generatePathSegment([
                'pid' => Events2Constants::PAGE_STORAGE,
                'title' => 'Weekly market',
            ]),
        );
    }
}
