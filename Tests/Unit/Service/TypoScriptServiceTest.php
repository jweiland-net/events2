<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\Utility;

use JWeiland\Events2\Service\TypoScriptService;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Test case.
 */
class TypoScriptServiceTest extends UnitTestCase
{
    protected TypoScriptService $subject;

    protected function setUp(): void
    {
        $this->subject = new TypoScriptService();
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject
        );
    }

    /**
     * @test
     */
    public function overrideWithEmptyValuesDoesNotOverrideAnything(): void
    {
        $flexFormSettings = [];
        $typoScriptSettings = [];
        $this->subject->override($flexFormSettings, $typoScriptSettings);

        self::assertSame(
            [],
            $flexFormSettings
        );
    }

    /**
     * @test
     */
    public function overrideWithNonSetFlexFormSettingWillUseValueOfTypoScript(): void
    {
        $flexFormSettings = [];
        $typoScriptSettings = [
            'foo' => 'bar'
        ];
        $this->subject->override($flexFormSettings, $typoScriptSettings);

        self::assertSame(
            [
                'foo' => 'bar'
            ],
            $flexFormSettings
        );
    }

    /**
     * @test
     */
    public function overrideWithZeroFlexFormSettingWillUseValueOfTypoScript(): void
    {
        $flexFormSettings = [
            'foo' => '0'
        ];
        $typoScriptSettings = [
            'foo' => 'bar'
        ];
        $this->subject->override($flexFormSettings, $typoScriptSettings);

        self::assertSame(
            [
                'foo' => 'bar'
            ],
            $flexFormSettings
        );
    }

    /**
     * @test
     */
    public function overrideWithEmptyFlexFormSettingWillUseValueOfTypoScript(): void
    {
        $flexFormSettings = [
            'foo' => ''
        ];
        $typoScriptSettings = [
            'foo' => 'bar'
        ];
        $this->subject->override($flexFormSettings, $typoScriptSettings);

        self::assertSame(
            [
                'foo' => 'bar'
            ],
            $flexFormSettings
        );
    }

    /**
     * @test
     */
    public function overrideWithFilledFlexFormAndTypoScriptSettingWillOverride(): void
    {
        $flexFormSettings = [
            'foo' => 'hello',
            'user' => [
                'first' => ''
            ]
        ];
        $typoScriptSettings = [
            'foo' => 'bar',
            'user' => [
                'first' => 'Stefan',
                'last' => 'Froemken',
            ]
        ];
        $this->subject->override($flexFormSettings, $typoScriptSettings);

        self::assertSame(
            [
                'foo' => 'hello',
                'user' => [
                    'first' => 'Stefan',
                    'last' => 'Froemken',
                ]
            ],
            $flexFormSettings
        );
    }
}
