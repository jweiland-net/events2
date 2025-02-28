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
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Form\Domain\Exception\RenderingException;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;

/**
 * This is a copy of the original EXT:form ArrayFormFactory
 * We add a possibility to modify the finishers before building
 */
class ArrayFormFactory extends \TYPO3\CMS\Form\Domain\Factory\ArrayFormFactory
{
    public function __construct(protected readonly ExtConf $extConf) {}

    /**
     * Build a form definition, depending on some configuration.
     *
     * @throws RenderingException
     */
    public function build(
        array $configuration,
        string $prototypeName = null,
        ?ServerRequestInterface $request = null,
    ): FormDefinition {
        $this->addEventUidToFormAction($configuration);
        $this->modifyEmailFinisherConfiguration($configuration);

        return parent::build($configuration, $prototypeName, $request);
    }

    /**
     * I haven't found any solution to add a dynamic GET var into the YAML configuration
     */
    protected function addEventUidToFormAction(array &$configuration): void
    {
        if (
            isset($_GET['tx_events2_management']['event'])
            && !isset($configuration['renderingOptions']['additionalParams']['tx_events2_events']['event'])
        ) {
            $configuration['renderingOptions']['additionalParams']['tx_events2_management']['event']
                = $_GET['tx_events2_management']['event'];
        }
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
                            $this->extConf->getEmailToAddress() => $this->extConf->getEmailToName(),
                        ];
                    }
                }
            }
        }
    }
}
