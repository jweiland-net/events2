<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Form;

use TYPO3\CMS\Form\Domain\Model\Exception\FormDefinitionConsistencyException;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\Renderable\AbstractRenderable;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;

/**
 * The hooks of EXT:form are called for all forms of the installation. The
 * hooks of events2 act on forms built by its ArrayFormFactory only.
 * The factory marks these forms with the rendering option "formOwner".
 * EXT:form sets the rendering options before it creates the elements, so
 * the mark is available in the hook "initializeFormElement", too.
 */
final class FormOwner
{
    public const RENDERING_OPTION = 'formOwner';

    public const EVENTS2 = 'events2';

    /**
     * @param array<string, mixed> $configuration Form definition
     * @return array<string, mixed>
     */
    public static function markConfiguration(array $configuration): array
    {
        $configuration['renderingOptions'][self::RENDERING_OPTION] = self::EVENTS2;

        return $configuration;
    }

    public static function isEvents2Form(?FormDefinition $formDefinition): bool
    {
        return $formDefinition instanceof FormDefinition
            && ($formDefinition->getRenderingOptions()[self::RENDERING_OPTION] ?? '') === self::EVENTS2;
    }

    /**
     * Returns false, if the renderable is not attached to a form (yet)
     */
    public static function isRenderableOfEvents2Form(RenderableInterface $renderable): bool
    {
        if ($renderable instanceof FormDefinition) {
            return self::isEvents2Form($renderable);
        }

        try {
            $formDefinition = $renderable instanceof AbstractRenderable ? $renderable->getRootForm() : null;
        } catch (FormDefinitionConsistencyException) {
            $formDefinition = null;
        }

        return self::isEvents2Form($formDefinition);
    }
}
