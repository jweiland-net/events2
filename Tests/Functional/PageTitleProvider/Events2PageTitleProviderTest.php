<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\PageTitleProvider;

use JWeiland\Events2\PageTitleProvider\Events2PageTitleProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for Events2PageTitleProvider
 */
class Events2PageTitleProviderTest extends FunctionalTestCase
{
    protected Events2PageTitleProvider $subject;

    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'sjbr/static-info-tables',
        'jweiland/events2',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        date_default_timezone_set('Europe/Berlin');

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/PageTitleProvider.csv');

        $this->subject = $this->getContainer()->get(Events2PageTitleProvider::class);
    }

    protected function tearDown(): void
    {
        unset(
            $this->pageTitleProvider,
        );

        parent::tearDown();
    }

    #[Test]
    public function findDayWithDateTimeOfTodayWillFindExactlyMatchingDay(): void
    {
        $request = new ServerRequest('https://www.example.com', 'GET');
        $request = $request->withQueryParams([
            'tx_events2_show' => [
                'controller' => 'Event',
                'action' => 'show',
                'event' => '1',
                'timestamp' => '1715299200',
            ],
        ]);

        $this->subject->setRequest($request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE));

        self::assertSame(
            'Nice title for detail page - 10.05.2024',
            $this->subject->getTitle(),
        );
    }
}
