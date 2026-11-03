<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Functional\EventListener;

use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Event\PreProcessControllerActionEvent;
use JWeiland\Events2\EventListener\ApplyUploadFolderEventListener;
use JWeiland\Events2\EventListener\CheckFalUploadsEventListener;
use JWeiland\Events2\Tests\Functional\Events2Constants;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Mvc\Controller\Arguments;
use TYPO3\CMS\Extbase\Mvc\Controller\FileUploadConfiguration;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Service\FileHandlingService;
use TYPO3\CMS\Extbase\Validation\ValidatorResolver;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test
 */
class ApplyUploadFolderEventListenerTest extends FunctionalTestCase
{
    private const DEFAULT_UPLOAD_FOLDER = '1:/user_upload/tx_events2/';

    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'sjbr/static-info-tables',
        'jweiland/events2',
    ];

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'phpTimeZone' => Events2Constants::PHP_TIMEZONE,
        ],
    ];

    #[Test]
    public function fileUploadAttributeOfImagesIsInitialized(): void
    {
        $configuration = $this->getImagesUploadConfiguration(
            $this->dispatchPreProcessControllerActionEvent('new', []),
        );

        self::assertSame(
            self::DEFAULT_UPLOAD_FOLDER,
            $configuration->getUploadFolder(),
        );
        self::assertSame(
            5,
            $configuration->getMaxFiles(),
        );
        self::assertSame(
            0,
            $configuration->getMinFiles(),
        );
    }

    public static function allowedActionDataProvider(): array
    {
        return [
            'create action' => ['create'],
            'update action' => ['update'],
        ];
    }

    #[Test]
    #[DataProvider('allowedActionDataProvider')]
    public function dispatchWithAllowedActionAppliesUploadFolderOfSettings(string $actionName): void
    {
        $configuration = $this->getImagesUploadConfiguration(
            $this->dispatchPreProcessControllerActionEvent(
                $actionName,
                ['new' => ['uploadFolder' => '1:user_upload/events/']],
            ),
        );

        self::assertSame(
            '1:user_upload/events/',
            $configuration->getUploadFolder(),
        );
    }

    #[Test]
    public function dispatchWithOtherActionKeepsUploadFolderOfAttribute(): void
    {
        $configuration = $this->getImagesUploadConfiguration(
            $this->dispatchPreProcessControllerActionEvent(
                'edit',
                ['new' => ['uploadFolder' => '1:user_upload/events/']],
            ),
        );

        self::assertSame(
            self::DEFAULT_UPLOAD_FOLDER,
            $configuration->getUploadFolder(),
        );
    }

    #[Test]
    public function dispatchWithEmptySettingKeepsUploadFolderOfAttribute(): void
    {
        $configuration = $this->getImagesUploadConfiguration(
            $this->dispatchPreProcessControllerActionEvent(
                'create',
                ['new' => ['uploadFolder' => '']],
            ),
        );

        self::assertSame(
            self::DEFAULT_UPLOAD_FOLDER,
            $configuration->getUploadFolder(),
        );
    }

    #[Test]
    public function dispatchWithoutCheckfaluploadsKeepsValidatorsOfAttribute(): void
    {
        $configuration = $this->getImagesUploadConfiguration(
            $this->dispatchPreProcessControllerActionEvent('create', []),
        );

        self::assertNotSame(
            [],
            $configuration->getValidators(),
        );

        foreach ($configuration->getValidators() as $validator) {
            self::assertStringStartsWith(
                'TYPO3\\CMS\\Extbase\\Validation\\Validator\\',
                $validator::class,
            );
        }
    }

    /**
     * Initializes the file upload configuration of Extbase and calls the
     * file upload listeners of events2 only, as other listeners need a
     * complete frontend request.
     *
     * @param array<string, mixed> $settings
     */
    private function dispatchPreProcessControllerActionEvent(
        string $actionName,
        array $settings,
    ): PreProcessControllerActionEvent {
        $request = $this->buildRequest($actionName);

        $arguments = new Arguments();
        $arguments
            ->addNewArgument('event', Event::class)
            ->setValidator($this->get(ValidatorResolver::class)->getBaseValidatorConjunction(Event::class));

        $this->get(FileHandlingService::class)->initializeFileUploadConfigurationsFromRequest($request, $arguments);

        $controllerActionEvent = new PreProcessControllerActionEvent($request, $arguments, $settings);
        (new ApplyUploadFolderEventListener())($controllerActionEvent);
        (new CheckFalUploadsEventListener($this->get(ValidatorResolver::class)))($controllerActionEvent);

        return $controllerActionEvent;
    }

    private function buildRequest(string $actionName): Request
    {
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $extbaseRequestParameters->setControllerExtensionName('Events2');
        $extbaseRequestParameters->setPluginName('Management');
        $extbaseRequestParameters->setControllerName('Management');
        $extbaseRequestParameters->setControllerActionName($actionName);

        return new Request(
            (new ServerRequest('https://www.example.com/', 'POST'))
                ->withAttribute('extbase', $extbaseRequestParameters),
        );
    }

    private function getImagesUploadConfiguration(
        PreProcessControllerActionEvent $controllerActionEvent,
    ): FileUploadConfiguration {
        $configuration = $controllerActionEvent
            ->getArguments()
            ->getArgument('event')
            ->getFileHandlingServiceConfiguration()
            ->getFileUploadConfigurationForProperty('images');

        self::assertInstanceOf(FileUploadConfiguration::class, $configuration);

        return $configuration;
    }
}
