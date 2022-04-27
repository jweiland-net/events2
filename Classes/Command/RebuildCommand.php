<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Command;

use JWeiland\Events2\Service\DatabaseService;
use JWeiland\Events2\Service\DayRelationService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/*
 * CLI Command
 */
class RebuildCommand extends Command
{
    protected DatabaseService $databaseService;

    protected DayRelationService $dayRelationService;

    protected OutputInterface $output;

    /**
     * Will be called by DI, so please don't add extbase classes with inject methods here.
     */
    public function __construct(
        DatabaseService $databaseService,
        DayRelationService $dayRelationService,
        string $name = null
    ) {
        parent::__construct($name);

        $this->databaseService = $databaseService;
        $this->dayRelationService = $dayRelationService;
    }

    protected function configure(): void
    {
        $this->setDescription(
            'Executing this command will TRUNCATE (delete) all records from day table. ' .
            'Afterwards, it searches for each current and future event and re-creates all day records again. ' .
            'If you have any problems with created day records, this command is the first place to start. ' .
            'Please be careful running this command as a CronJob each day, as for the time it runs, there ' .
            'may no events visible in frontend. We prefer using the Scheduler Task manually.'
        );
    }

    /**
     * Delete and re-create all day records of events2
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        $output->writeln('Start re-building day records for each event record');
        $output->writeln('');
        $this->truncateDayTable();
        $output->writeln('');
        $this->reGenerateDayRelations();
        $output->writeln('');

        return 0;
    }

    protected function truncateDayTable(): void
    {
        $this->databaseService->truncateTable('tx_events2_domain_model_day', true);
        $this->output->writeln('I have truncated the day table');
    }

    protected function reGenerateDayRelations(): void
    {
        $eventCounter = 0;
        $dayCounter = 0;
        $this->output->writeln('Process each event record');

        $rows = $this->databaseService->getCurrentAndFutureEvents();
        foreach ($rows as $row) {
            $eventRecord = $this->dayRelationService->createDayRelations((int)$row['uid']);
            if ($eventRecord !== []) {
                if (is_array($eventRecord['days'])) {
                    $amountOfDayRecords = count($eventRecord['days'] ?? []);
                    $this->output->writeln(sprintf(
                        'Process event UID: %09d, PID: %05d, created: %04d day records, RAM: %d',
                        $eventRecord['uid'],
                        $eventRecord['pid'],
                        $amountOfDayRecords,
                        memory_get_usage()
                    ));
                    ++$eventCounter;
                    $dayCounter += $amountOfDayRecords;
                } else {
                    $this->output->writeln(sprintf(
                        'ERROR event UID: %09d, PID: %05d: array key "days" has to be an array.',
                        $row['uid'],
                        $row['pid']
                    ));
                }
            } else {
                $this->output->writeln(sprintf(
                    'ERROR event UID: %09d, PID: %05d: event record could not be fetched from DB.',
                    $row['uid'],
                    $row['pid']
                ));
            }
        }

        $this->output->writeln(sprintf(
            'We have recreated the day records for %d event records and %d day records in total',
            $eventCounter,
            $dayCounter
        ));
    }
}
