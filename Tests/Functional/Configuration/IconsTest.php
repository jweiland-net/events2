<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Configuration;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test
 */
class IconsTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'jweiland/events2',
    ];

    #[Test]
    public function iconsOfExtensionAreSvgFilesWithoutStyleElements(): void
    {
        $iconRegistry = $this->get(IconRegistry::class);
        $identifiers = array_filter(
            $iconRegistry->getAllRegisteredIconIdentifiers(),
            static fn(string $identifier): bool => str_starts_with($identifier, 'ext-events2-'),
        );

        self::assertNotEmpty($identifiers);
        foreach ($identifiers as $identifier) {
            $configuration = $iconRegistry->getIconConfigurationByIdentifier($identifier);
            $file = GeneralUtility::getFileAbsFileName($configuration['options']['source']);

            self::assertSame(SvgIconProvider::class, $configuration['provider'], $identifier);
            self::assertFileExists($file, $identifier);
            // Inline rendered SVG icons must not share class based styles
            self::assertStringNotContainsString('<style', (string)file_get_contents($file), $identifier);
        }
    }

    #[Test]
    public function iconsOfTablesAreRegistered(): void
    {
        $iconRegistry = $this->get(IconRegistry::class);
        foreach ($GLOBALS['TCA'] as $table => $configuration) {
            if (!str_starts_with($table, 'tx_events2_')) {
                continue;
            }

            self::assertArrayNotHasKey('iconfile', $configuration['ctrl'], $table);
            foreach ($configuration['ctrl']['typeicon_classes'] ?? [] as $identifier) {
                self::assertTrue($iconRegistry->isRegistered($identifier), $table . ': ' . $identifier);
            }
        }
    }
}
