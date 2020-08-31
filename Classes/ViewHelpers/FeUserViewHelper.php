<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\ViewHelpers;

use JWeiland\Events2\Domain\Repository\UserRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/*
 * This VH returns values from current logged in frontend user array
 */
class FeUserViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Initialize all arguments. You need to override this method and call
     * $this->registerArgument(...) inside this method, to register all your arguments.
     */
    public function initializeArguments()
    {
        $this->registerArgument('field', 'string', 'The field/ArrayKey from currently logged in frontend user', false, 'uid');
    }

    /**
     * Implements a ViewHelper to get values from current logged in fe_user.
     *
     * @param array $arguments
     * @param \Closure $childClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(
        array $arguments,
        \Closure $childClosure,
        RenderingContextInterface $renderingContext
    ) {
        $userRepository = GeneralUtility::makeInstance(UserRepository::class);
        return $userRepository->getFieldFromUser($arguments['field']);
    }
}
