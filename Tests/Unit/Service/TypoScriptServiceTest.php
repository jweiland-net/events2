<?php

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
    /**
     * @var TypoScriptService
     */
    protected $subject;

    public function setUp()
    {
        $this->subject = new TypoScriptService();
    }

    public function tearDown()
    {
        unset(
            $this->subject
        );
    }

    /**
     * @test
     */
    public function overrideWithEmptyValuesDoesNotOverrideAnything()
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
    public function overrideWithNonSetFlexFormSettingWillUseValueOfTypoScript()
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
    public function overrideWithZeroFlexFormSettingWillUseValueOfTypoScript()
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
    public function overrideWithEmptyFlexFormSettingWillUseValueOfTypoScript()
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
    public function overrideWithFilledFlexFormAndTypoScriptSettingWillOverride()
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
