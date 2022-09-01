<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Factory;

use JWeiland\Events2\Configuration\ExtConf;
use TYPO3\CMS\Form\Domain\Exception\RenderingException;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;

/**
 * This is a copy of the original EXT:form ArrayFormFactory
 * We add a possibility to modify the finishers before building
 */
class ArrayFormFactory extends \TYPO3\CMS\Form\Domain\Factory\ArrayFormFactory
{
    /**
     * @var ExtConf
     */
    protected $extConf;

    public function __construct(ExtConf $extConf)
    {
        $this->extConf = $extConf;
    }

    /**
     * Build a form definition, depending on some configuration.
     *
     * @param array $configuration
     * @param ?string $prototypeName
     * @return FormDefinition
     * @throws RenderingException
     * @internal
     */
    public function build(array $configuration, string $prototypeName = null): FormDefinition
    {
        $this->modifyEmailFinisherConfiguration($configuration);

        return parent::build($configuration, $prototypeName);
    }

    protected function modifyEmailFinisherConfiguration(array &$configuration): void
    {
        if (
            isset($configuration['finishers'])
            && is_array($configuration['finishers'])
            && $configuration['finishers'] !== []
        ) {
            foreach ($configuration['finishers'] as &$finisherConfiguration) {
                if (
                    isset($finisherConfiguration['identifier'])
                    && $finisherConfiguration['identifier'] === 'EmailToReceiver'
                ) {
                    if (!isset($finisherConfiguration['options'])) {
                        $finisherConfiguration['options'] = [];
                    }

                    if (!isset($finisherConfiguration['options']['senderName'])) {
                        $finisherConfiguration['options']['senderName'] = $this->extConf->getEmailFromName();
                    }
                    if (!isset($finisherConfiguration['options']['senderAddress'])) {
                        $finisherConfiguration['options']['senderAddress'] = $this->extConf->getEmailFromAddress();
                    }
                    if (!isset($finisherConfiguration['options']['recipients'])) {
                        $finisherConfiguration['options']['recipients'] = [
                            $this->extConf->getEmailToAddress() => $this->extConf->getEmailToName()
                        ];
                    }
                }
            }
        }
    }
}
