<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\DataHandling;

use JWeiland\Events2\Tests\Functional\Traits\InsertEventTrait;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for DataHandler
 */
class DataHandlerTest extends FunctionalTestCase
{
    use InsertEventTrait;

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

        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['BE_USER']->workspace = 0;

        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageServiceFactory::class)->createFromUserPreferences($GLOBALS['BE_USER']);

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Events2PageTree.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_groups.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');

        $eventBegin = new \DateTimeImmutable('midnight');
        $eventBegin = $eventBegin->modify('first day of this month');
        $eventBegin = $eventBegin->modify('+4 days');
        $eventBegin = $eventBegin->modify('-2 months');

        $this->insertEvent(
            title: 'Week market',
            eventBegin: $eventBegin,
            additionalFields: [
                'event_type' => 'recurring',
                'xth' => 31,
                'weekday' => 16,
                'each_weeks' => 0,
                'each_months' => 0,
                'recurring_end' => 0,
            ],
            organizer: 'Stefan',
            location: 'Market',
        );
    }

    #[Test]
    public function deleteEventByAdminWillRemoveDayRecords(): void
    {
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['BE_USER']->user['username'] = 'acceptanceTestSetup';
        $GLOBALS['BE_USER']->user['admin'] = 1;
        $GLOBALS['BE_USER']->user['uid'] = 1;
        $GLOBALS['BE_USER']->workspace = 0;

        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [],
            [
                'tx_events2_domain_model_event' => [
                    1 => [
                        'delete' => 1,
                    ],
                ],
            ],
        );
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        $eventBegin = new \DateTimeImmutable('today midnight');
        $eventBegin = $eventBegin->modify('first day of this month');

        $eventEnd = new \DateTimeImmutable('today midnight');
        $eventEnd = $eventEnd->modify('last day of this month');

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_day');
        $numberOfDeletedDays = $queryBuilder
            ->count('*')
            ->from('tx_events2_domain_model_day')
            ->where(
                $queryBuilder->expr()->gte(
                    'day',
                    $queryBuilder->createNamedParameter($eventBegin->format('U'), Connection::PARAM_INT),
                ),
                $queryBuilder->expr()->lte(
                    'day',
                    $queryBuilder->createNamedParameter($eventEnd->format('U'), Connection::PARAM_INT),
                ),
            )
            ->executeQuery()
            ->fetchOne();

        self::assertSame(
            0,
            $numberOfDeletedDays,
        );
    }

    #[Test]
    public function deleteEventByEditorWillRemoveDayRecords(): void
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('be_users');
        $user = $queryBuilder
            ->select('*')
            ->from('be_users')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter(2, Connection::PARAM_INT),
                ),
            )
            ->executeQuery()
            ->fetchAssociative();

        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['BE_USER']->user = $user;
        $GLOBALS['BE_USER']->workspace = 0;
        $GLOBALS['BE_USER']->fetchGroupData();

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->admin = false;
        $dataHandler->start(
            [],
            [
                'tx_events2_domain_model_event' => [
                    1 => [
                        'delete' => 1,
                    ],
                ],
            ],
        );
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        $eventBegin = new \DateTimeImmutable('today midnight');
        $eventBegin = $eventBegin->modify('first day of this month');

        $eventEnd = new \DateTimeImmutable('today midnight');
        $eventEnd = $eventEnd->modify('last day of this month');

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_events2_domain_model_day');
        $queryBuilder->getRestrictions()->removeAll();
        $amountOfDeletedDays = $queryBuilder
            ->count('*')
            ->from('tx_events2_domain_model_day')
            ->where(
                $queryBuilder->expr()->gte(
                    'day',
                    $queryBuilder->createNamedParameter($eventBegin->format('U'), Connection::PARAM_INT),
                ),
                $queryBuilder->expr()->lt(
                    'day',
                    $queryBuilder->createNamedParameter($eventEnd->format('U'), Connection::PARAM_INT),
                ),
            )
            ->executeQuery()
            ->fetchOne();

        self::assertSame(
            0,
            $amountOfDeletedDays,
        );
    }
}
