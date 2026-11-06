<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\Form;

use JWeiland\Events2\Domain\Factory\ArrayFormFactory;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The teaser (element "short-description") of NewEvent.form.yaml must not
 * be longer than "max" of the column "teaser" in TCA.
 */
class TeaserLengthTest extends FunctionalTestCase
{
    private const FORM_DEFINITION = 'EXT:events2/Configuration/Form/NewEvent.form.yaml';

    protected array $coreExtensionsToLoad = [
        'form',
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'jweiland/events2',
    ];

    protected array $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'events2' => [
                'emailFromAddress' => 'projects@example.com',
                'emailFromName' => 'Events2',
                'emailToAddress' => 'hosting@example.com',
                'emailToName' => 'Events2',
            ],
        ],
        'SYS' => [
            'phpTimeZone' => Events2Constants::PHP_TIMEZONE,
        ],
    ];

    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);

        parent::tearDown();
    }

    #[Test]
    public function maxLengthOfTeaserMatchesMaxOfTca(): void
    {
        $element = $this->buildTeaserElement();

        self::assertSame(
            $this->getMaxOfTca(),
            (int)$element->getProperties()['fluidAdditionalAttributes']['maxlength'],
        );
    }

    #[Test]
    public function stringLengthValidatorOfTeaserMatchesMaxOfTca(): void
    {
        $stringLengthValidators = array_values(array_filter(
            iterator_to_array($this->buildTeaserElement()->getValidators(), false),
            static fn(object $validator): bool => $validator instanceof StringLengthValidator,
        ));

        self::assertCount(1, $stringLengthValidators);
        self::assertSame($this->getMaxOfTca(), (int)$stringLengthValidators[0]->getOptions()['maximum']);
    }

    public static function teaserDataProvider(): array
    {
        return [
            'empty teaser' => ['', true],
            '255 letters' => [str_repeat('a', 255), true],
            '255 multibyte letters' => [str_repeat('ä', 255), true],
            '256 letters' => [str_repeat('a', 256), false],
            '434 letters' => [str_repeat('a', 434), false],
        ];
    }

    #[Test]
    #[DataProvider('teaserDataProvider')]
    public function validatorsOfTeaserValidateLength(string $teaser, bool $isValid): void
    {
        $hasErrors = false;
        foreach ($this->buildTeaserElement()->getValidators() as $validator) {
            $hasErrors = $hasErrors || $validator->validate($teaser)->hasErrors();
        }

        self::assertSame($isValid, !$hasErrors);
    }

    private function getMaxOfTca(): int
    {
        return (int)$GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['teaser']['config']['max'];
    }

    private function buildTeaserElement(): FormElementInterface
    {
        $GLOBALS['TYPO3_REQUEST'] = $this->buildRequest();
        $this->get(ConfigurationManagerInterface::class)->setRequest($GLOBALS['TYPO3_REQUEST']);

        $formDefinition = $this->get(ArrayFormFactory::class)->build(
            Yaml::parseFile(GeneralUtility::getFileAbsFileName(self::FORM_DEFINITION)),
            'standard',
            $GLOBALS['TYPO3_REQUEST'],
        );

        return $formDefinition->getElementByIdentifier('short-description');
    }

    private function buildRequest(): ServerRequest
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([
            'plugin.' => [
                'tx_form.' => ['settings.' => ['yamlConfigurations.' => [
                    '10' => 'EXT:form/Configuration/Yaml/FormSetup.yaml',
                    '1661247670' => 'EXT:events2/Configuration/Form/OverrideFormSettings.yaml',
                ]]],
            ],
        ]);

        return (new ServerRequest('https://www.example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript)
            ->withAttribute('language', new SiteLanguage(0, 'en_US.UTF-8', new Uri('https://www.example.com/'), []));
    }
}
