<?php

declare(strict_types=1);

namespace JWeiland\Events2\Tests\Functional\ViewHelpers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

/**
 * Functional test for CreateYoutubeUriViewHelper using TYPO3 13+ RenderingContext
 */
class CreateYoutubeUriViewHelperTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'extensionmanager',
        'reactions',
    ];

    protected array $testExtensionsToLoad = [
        'sjbr/static-info-tables',
        'jweiland/events2',
    ];

    /**
     * Data provider for various YouTube URL formats
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function youtubeUrlDataProvider(): array
    {
        return [
            'Standard URL' => [
                'https://www.youtube.com/watch?v=Sql0rc86rQ8',
                '//www.youtube.com/embed/Sql0rc86rQ8'
            ],
            'Short URL with tracking' => [
                'https://youtu.be/E01tqcTwplA?si=qOrHia518c9sMdiD',
                '//www.youtube.com/embed/E01tqcTwplA'
            ],
            'Short URL with feature param' => [
                'https://youtu.be/Sql0rc86rQ8?feature=shared',
                '//www.youtube.com/embed/Sql0rc86rQ8'
            ],
            'Shorts URL' => [
                'https://www.youtube.com/shorts/Sql0rc86rQ8',
                '//www.youtube.com/embed/Sql0rc86rQ8'
            ],
            'Live URL' => [
                'https://www.youtube.com/live/Sql0rc86rQ8',
                '//www.youtube.com/embed/Sql0rc86rQ8'
            ],
            'Raw ID Fallback' => [
                'Sql0rc86rQ8',
                '//www.youtube.com/embed/Sql0rc86rQ8'
            ]
        ];
    }

    #[Test]
    #[DataProvider('youtubeUrlDataProvider')]
    public function renderReturnsCorrectEmbedUrl(string $inputLink, string $expectedEmbedUrl): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getVariableProvider()->add('youtubeLink', $inputLink);

        // Define the template source with your ViewHelper namespace
        $templateSource = '
            {namespace e2=JWeiland\Events2\ViewHelpers}
            {youtubeLink -> e2:createYoutubeUri()}';

        $context->getTemplatePaths()->setTemplateSource($templateSource);

        $view = new TemplateView($context);
        $output = trim($view->render());

        self::assertEquals($expectedEmbedUrl, $output);
    }
}
