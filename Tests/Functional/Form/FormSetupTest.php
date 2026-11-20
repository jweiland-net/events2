<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Form;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplatePaths;

/**
 * The form setup of events2 is loaded for all forms of the
 * installation. It must not change the mails of other forms.
 */
class FormSetupTest extends FunctionalTestCase
{
    private const CORE_FORM_SETUP = 'EXT:form/Configuration/Yaml/FormSetup.yaml';

    private const FORM_SETUP = 'EXT:events2/Configuration/Form/OverrideFormSettings.yaml';

    private const TEMPLATE_PATH = 'EXT:events2/Resources/Private/FormFramework/Templates/Finishers/Email/';
    protected array $coreExtensionsToLoad = [
        'form',
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'jweiland/events2',
    ];

    #[Test]
    public function formSetupKeepsEmailFinisherOfCore(): void
    {
        self::assertSame(
            $this->getEmailFinisherDefinition([10 => self::CORE_FORM_SETUP]),
            $this->getEmailFinisherDefinition([10 => self::CORE_FORM_SETUP, 1661247670 => self::FORM_SETUP]),
        );
    }

    #[Test]
    public function formSetupKeepsTranslationFilesOfCore(): void
    {
        self::assertSame(
            $this->getPrototype([10 => self::CORE_FORM_SETUP])['formElementsDefinition']['Form']['renderingOptions']['translation'],
            $this->getPrototype([10 => self::CORE_FORM_SETUP, 1661247670 => self::FORM_SETUP])['formElementsDefinition']['Form']['renderingOptions']['translation'],
        );
    }

    #[Test]
    public function eventFormUsesOwnTranslationFile(): void
    {
        $formDefinition = Yaml::parseFile(__DIR__ . '/../../../Configuration/Form/NewEvent.form.yaml');

        self::assertSame(
            'EXT:events2/Resources/Private/Language/locallang_ext_form.xlf',
            $formDefinition['renderingOptions']['translation']['translationFiles'][20],
        );
    }

    #[Test]
    public function formSetupRegistersSaveEventFinisher(): void
    {
        $prototype = $this->getPrototype([10 => self::CORE_FORM_SETUP, 1661247670 => self::FORM_SETUP]);

        self::assertArrayHasKey('SaveEventFinisher', $prototype['finishersDefinition']);
    }

    #[Test]
    public function eventFormUsesOwnMailTemplate(): void
    {
        $options = $this->getEmailFinisherOptionsOfEventForm();

        self::assertSame('Default', $options['templateName']);
        self::assertSame(self::TEMPLATE_PATH, end($options['templateRootPaths']));

        $templatePaths = new TemplatePaths();
        $templatePaths->setTemplateRootPaths(array_map(GeneralUtility::getFileAbsFileName(...), $options['templateRootPaths']));
        foreach (['html', 'txt'] as $format) {
            self::assertSame(
                GeneralUtility::getFileAbsFileName(self::TEMPLATE_PATH . 'Default.' . $format),
                $templatePaths->resolveTemplateFileForControllerAndActionAndFormat('Default', 'Default', $format),
            );
        }
    }

    #[Test]
    public function mailTemplateUsesModerationTokenOfFinisher(): void
    {
        foreach (['html', 'txt'] as $format) {
            $template = (string)file_get_contents(GeneralUtility::getFileAbsFileName(self::TEMPLATE_PATH . 'Default.' . $format));

            self::assertStringContainsString('{finisherVariableProvider.SaveEvent.moderationToken}', $template);
        }
    }

    /**
     * Options of EmailToReceiver like FormDefinition::createFinisher() merges them
     *
     * @return array<string, mixed>
     */
    private function getEmailFinisherOptionsOfEventForm(): array
    {
        $options = $this->getEmailFinisherDefinition([10 => self::CORE_FORM_SETUP, 1661247670 => self::FORM_SETUP])['options'];
        $formDefinition = Yaml::parseFile(__DIR__ . '/../../../Configuration/Form/NewEvent.form.yaml');
        foreach ($formDefinition['finishers'] as $finisher) {
            if ($finisher['identifier'] === 'EmailToReceiver') {
                ArrayUtility::mergeRecursiveWithOverrule($options, $finisher['options']);
            }
        }
        ksort($options['templateRootPaths']);

        return $options;
    }

    /**
     * @param array<int, string> $yamlConfigurations
     * @return array<string, mixed>
     */
    private function getEmailFinisherDefinition(array $yamlConfigurations): array
    {
        return $this->getPrototype($yamlConfigurations)['finishersDefinition']['EmailToReceiver'];
    }

    /**
     * @param array<int, string> $yamlConfigurations
     * @return array<string, mixed>
     */
    private function getPrototype(array $yamlConfigurations): array
    {
        $configuration = $this->get(ConfigurationManagerInterface::class)
            ->getYamlConfiguration(['yamlConfigurations' => $yamlConfigurations], true);

        return ($configuration['prototypes'] ?? $configuration['TYPO3']['CMS']['Form']['prototypes'])['standard'];
    }
}
