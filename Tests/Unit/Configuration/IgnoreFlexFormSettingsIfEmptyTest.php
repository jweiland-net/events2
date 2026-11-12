<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Configuration;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Each FlexForm setting has to fall back to TypoScript, if its FlexForm value is empty
 */
class IgnoreFlexFormSettingsIfEmptyTest extends UnitTestCase
{
    private const EXTENSION_PATH = __DIR__ . '/../../../';

    #[Test]
    public function allFlexFormSettingsAreConfiguredToBeIgnoredIfEmpty(): void
    {
        $ignoredSettings = $this->getIgnoredSettings();

        foreach ($this->getFlexFormSettings() as $flexFormSetting) {
            self::assertContains(
                $flexFormSetting,
                $ignoredSettings,
                'FlexForm setting "' . $flexFormSetting . '" is missing in ignoreFlexFormSettingsIfEmpty',
            );
        }
    }

    #[Test]
    public function ignoredSettingsAreAvailableInFlexForms(): void
    {
        $flexFormSettings = $this->getFlexFormSettings();

        foreach ($this->getIgnoredSettings() as $ignoredSetting) {
            self::assertContains($ignoredSetting, $flexFormSettings);
        }
    }

    /**
     * @return string[]
     */
    private function getIgnoredSettings(): array
    {
        $typoScript = (string)file_get_contents(
            self::EXTENSION_PATH . 'Configuration/Sets/Events2/setup.typoscript',
        );
        if (!preg_match('/ignoreFlexFormSettingsIfEmpty \((.*?)\n\s*\)/s', $typoScript, $matches)) {
            self::fail('ignoreFlexFormSettingsIfEmpty not found in setup.typoscript');
        }

        return array_values(array_filter(array_map('trim', explode(',', $matches[1]))));
    }

    /**
     * @return string[]
     */
    private function getFlexFormSettings(): array
    {
        $settings = [];
        foreach (glob(self::EXTENSION_PATH . 'Configuration/FlexForms/*.xml') ?: [] as $flexFormFile) {
            preg_match_all('/<settings\.([a-zA-Z0-9.]+)>/', (string)file_get_contents($flexFormFile), $matches);
            array_push($settings, ...$matches[1]);
        }

        return array_values(array_unique($settings));
    }
}
