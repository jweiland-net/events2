<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Command;

use JWeiland\Events2\Importer\XmlImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Import events from an XML file. On success, the XML file will be deleted.
 */
#[AsCommand(
    name: 'events2:import',
    description: 'Import events from an XML file into events2. The file will be deleted after a successful import.',
)]
final class ImportCommand extends Command
{
    /**
     * Will be called by DI, so please don't add extbase classes with inject methods here.
     */
    public function __construct(
        private readonly XmlImporter $xmlImporter,
        private readonly ResourceFactory $resourceFactory,
        private readonly FlashMessageService $flashMessageService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp(
            'Imports the events of the given XML file into the given storage page. The XML file has to comply '
            . 'with EXT:events2/Resources/Public/XmlImportValidator.xsd. All messages of the import will be '
            . 'written to the file "Messages.txt" next to the XML file. On success, the XML file will be deleted. '
            . 'If the XML file does not exist, the command ends successfully without doing anything.',
        );
        $this->addArgument(
            'path',
            InputArgument::REQUIRED,
            'Combined identifier of the XML file to import. Format: [storage UID]:[path to file]. '
            . 'Example: 1:/event_import/Import.xml',
        );
        $this->addArgument(
            'storagePid',
            InputArgument::REQUIRED,
            'UID of the page where the imported records will be stored',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $storagePid = (int)$input->getArgument('storagePid');
        if ($storagePid <= 0) {
            $io->error('Argument storagePid has to be a page UID greater than 0.');
            return Command::INVALID;
        }

        try {
            $file = $this->resourceFactory->retrieveFileOrFolderObject((string)$input->getArgument('path'));
        } catch (\Exception) {
            $io->info('Currently no file for import found.');
            return Command::SUCCESS;
        }

        if (!$file instanceof File || $file->isMissing()) {
            $io->error('The defined path is not a valid file or the file is missing. Please re-check the path.');
            return Command::FAILURE;
        }

        return $this->importFile($file, $storagePid, $io);
    }

    private function importFile(File $file, int $storagePid, SymfonyStyle $io): int
    {
        // The file may have been overwritten via FTP or SCP. Update its FAL information first.
        GeneralUtility::makeInstance(Indexer::class, $file->getStorage())->updateIndexEntry($file);

        $this->xmlImporter->setStoragePid($storagePid);
        $this->xmlImporter->setFile($file);

        try {
            $isImported = $this->xmlImporter->checkFile() && $this->xmlImporter->import();
        } catch (\Exception $exception) {
            $io->error($exception->getMessage());
            $isImported = false;
        }

        $this->outputImporterMessages($io);

        if (!$isImported) {
            $io->error('Import failed. Please check the messages above or in "Messages.txt" next to the XML file.');
            return Command::FAILURE;
        }

        $file->delete();
        $io->success('Import finished. The XML file has been deleted.');

        return Command::SUCCESS;
    }

    /**
     * The XmlImporter collects its messages as FlashMessages. Show them on CLI.
     */
    private function outputImporterMessages(SymfonyStyle $io): void
    {
        $flashMessages = $this->flashMessageService->getMessageQueueByIdentifier()->getAllMessagesAndFlush();
        foreach ($flashMessages as $flashMessage) {
            match ($flashMessage->getSeverity()) {
                ContextualFeedbackSeverity::ERROR => $io->error($flashMessage->getMessage()),
                ContextualFeedbackSeverity::WARNING => $io->warning($flashMessage->getMessage()),
                default => $io->text($flashMessage->getMessage()),
            };
        }
    }
}
