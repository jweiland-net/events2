<?php

namespace JWeiland\Events2\ViewHelpers;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use JWeiland\Events2\Domain\Repository\UserRepository;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FeUserViewHelper extends AbstractViewHelper
{
    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * inject userRepository
     *
     * @param UserRepository $userRepository
     *
     * @return void
     */
    public function injectUserRepository(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Implements a ViewHelper to get values from current logged in fe_user.
     *
     * @param string $field Field to retrieve value from
     *
     * @return string
     */
    public function render($field = 'uid')
    {
        return $this->userRepository->getFieldFromUser($field);
    }
}
