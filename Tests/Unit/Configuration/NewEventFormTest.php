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
use Symfony\Component\Yaml\Yaml;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for the form definition of EXT:form
 */
class NewEventFormTest extends UnitTestCase
{
    #[Test]
    public function teaserIsStoredAsPlainText(): void
    {
        $elements = $this->getElementsOfEventFinisher();

        self::assertTrue($elements['short-description']['normalizePlainText'] ?? false);
        self::assertArrayNotHasKey('convertPlainTextToHtml', $elements['short-description']);
    }

    #[Test]
    public function detailInformationIsStoredAsHtml(): void
    {
        $elements = $this->getElementsOfEventFinisher();

        self::assertTrue($elements['detail-description']['convertPlainTextToHtml'] ?? false);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getElementsOfEventFinisher(): array
    {
        $formDefinition = Yaml::parseFile(__DIR__ . '/../../../Configuration/Form/NewEvent.form.yaml');

        foreach ($formDefinition['finishers'][1]['options'] as $option) {
            if (($option['table'] ?? '') === 'tx_events2_domain_model_event') {
                return $option['elements'];
            }
        }

        self::fail('Mapping of event table not found in SaveEventFinisher');
    }
}
