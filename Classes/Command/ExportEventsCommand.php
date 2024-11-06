<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Command;

use JWeiland\Events2\Exporter\EventsExporter;
use JWeiland\Events2\Exporter\ExporterConfiguration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Prepare events2 records for export and send them to another TYPO3 system with activated EXT:reactions
 */
class ExportEventsCommand extends Command
{
    protected OutputInterface $output;

    /**
     * Will be called by DI, so please don't add extbase classes with inject methods here.
     */
    public function __construct(
        protected readonly EventsExporter $eventsExporter,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'url',
            InputArgument::REQUIRED,
            'Set URL to where the events2 records should be exported. Hint: Copy URL from EXT:reactions module',
        );
        $this->addArgument(
            'secret',
            InputArgument::REQUIRED,
            'To validate the request you have to enter the secret. Copy from EXT:reactions module while creating a new reaction. It is not visible again after storing the reaction.',
        );
        $this->addArgument(
            'storagePages',
            InputArgument::REQUIRED,
            'Set storage page UIDs to collect events from. Divide multiple storages with comma',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configuration = new ExporterConfiguration(
            (string)$input->getArgument('url'),
            (string)$input->getArgument('secret'),
            $this->getStoragePages($input),
        );

        $response = $this->eventsExporter->export($configuration);
        $body = (string)$response->getBody();
        if (!\json_validate($body)) {
            $output->writeln('<error>Invalid JSON response</error>');
            return Command::FAILURE;
        }

        try {
            $status = \json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $output->writeln('<error>JSON string can not be decoded</error>');
            return Command::FAILURE;
        }

        if ($response->getStatusCode() !== 200 || $status['success'] === false) {
            $output->writeln('<error>' . $status['error'] . '</error>');
            return Command::FAILURE;
        }

        $output->writeln('<info>' . $status['message'] . '</info>');
        return Command::SUCCESS;
    }

    protected function getStoragePages(InputInterface $input): array
    {
        $storagePages = (string)$input->getArgument('storagePages');

        return GeneralUtility::intExplode(',', $storagePages, true);
    }
}
