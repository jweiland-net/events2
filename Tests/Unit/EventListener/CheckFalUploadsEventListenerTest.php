<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Tests\Unit\EventListener;

use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Event\PreProcessControllerActionEvent;
use JWeiland\Events2\EventListener\CheckFalUploadsEventListener;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\Arguments;
use TYPO3\CMS\Extbase\Mvc\Controller\FileUploadConfiguration;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;
use TYPO3\CMS\Extbase\Validation\ValidatorResolver;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case.
 */
class CheckFalUploadsEventListenerTest extends UnitTestCase
{
    private const VALIDATOR = 'JWeiland\\Checkfaluploads\\Validation\\Validator\\CheckFalUploadValidator';

    protected CheckFalUploadsEventListener $subject;

    protected ValidatorResolver|MockObject $validatorResolverMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validatorResolverMock = $this->createMock(ValidatorResolver::class);

        $this->subject = new CheckFalUploadsEventListener(
            $this->validatorResolverMock,
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
            $this->validatorResolverMock,
        );

        parent::tearDown();
    }

    #[Test]
    public function invokeWithoutCheckfaluploadsWillNotAddValidator(): void
    {
        $this->setCheckfaluploadsLoaded(false);

        $this->validatorResolverMock
            ->expects(self::never())
            ->method('createValidator');

        $controllerActionEvent = $this->buildControllerActionEvent('create');

        ($this->subject)($controllerActionEvent);

        self::assertSame(
            [],
            $this->getImagesUploadConfiguration($controllerActionEvent)->getValidators(),
        );
    }

    #[Test]
    public function invokeWithOtherActionWillNotAddValidator(): void
    {
        $this->setCheckfaluploadsLoaded(true);

        $this->validatorResolverMock
            ->expects(self::never())
            ->method('createValidator');

        $controllerActionEvent = $this->buildControllerActionEvent('new');

        ($this->subject)($controllerActionEvent);

        self::assertSame(
            [],
            $this->getImagesUploadConfiguration($controllerActionEvent)->getValidators(),
        );
    }

    #[Test]
    public function invokeWithoutEventArgumentWillNotAddValidator(): void
    {
        $this->setCheckfaluploadsLoaded(true);

        $this->validatorResolverMock
            ->expects(self::never())
            ->method('createValidator');

        $request = $this->buildRequest('create');

        ($this->subject)(new PreProcessControllerActionEvent($request, new Arguments(), []));
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
    public function invokeWithAllowedActionWillAddValidator(string $actionName): void
    {
        $this->setCheckfaluploadsLoaded(true);

        $controllerActionEvent = $this->buildControllerActionEvent($actionName);
        $validator = self::createStub(ValidatorInterface::class);

        $this->validatorResolverMock
            ->expects(self::once())
            ->method('createValidator')
            ->with(
                self::VALIDATOR,
                ['propertyPath' => 'event.images'],
                $controllerActionEvent->getRequest(),
            )
            ->willReturn($validator);

        ($this->subject)($controllerActionEvent);

        self::assertSame(
            [$validator],
            $this->getImagesUploadConfiguration($controllerActionEvent)->getValidators(),
        );
    }

    private function setCheckfaluploadsLoaded(bool $isLoaded): void
    {
        $packageManagerStub = self::createStub(PackageManager::class);
        $packageManagerStub
            ->method('isPackageActive')
            ->willReturnCallback(
                static fn(string $packageKey): bool => $packageKey === 'checkfaluploads' && $isLoaded,
            );

        ExtensionManagementUtility::setPackageManager($packageManagerStub);
    }

    private function buildControllerActionEvent(string $actionName): PreProcessControllerActionEvent
    {
        $arguments = new Arguments();
        $arguments
            ->addNewArgument('event', Event::class)
            ->getFileHandlingServiceConfiguration()
            ->addFileUploadConfiguration(new FileUploadConfiguration('images'));

        return new PreProcessControllerActionEvent($this->buildRequest($actionName), $arguments, []);
    }

    private function buildRequest(string $actionName): Request
    {
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $extbaseRequestParameters->setControllerName('Management');
        $extbaseRequestParameters->setControllerActionName($actionName);

        return new Request(
            (new ServerRequest())->withAttribute('extbase', $extbaseRequestParameters),
        );
    }

    private function getImagesUploadConfiguration(
        PreProcessControllerActionEvent $controllerActionEvent,
    ): FileUploadConfiguration {
        return $controllerActionEvent
            ->getArguments()
            ->getArgument('event')
            ->getFileHandlingServiceConfiguration()
            ->getFileUploadConfigurationForProperty('images');
    }
}
