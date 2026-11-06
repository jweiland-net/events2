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
use JWeiland\Events2\Form\RichTextFormElementConfigurator;
use JWeiland\Events2\Service\ManagementAccessService;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Form\Domain\Exception\RenderingException;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;

/**
 * This is a copy of the original EXT:form ArrayFormFactory
 * We add a possibility to modify the finishers before building
 */
class ArrayFormFactory extends \TYPO3\CMS\Form\Domain\Factory\ArrayFormFactory
{
    public function __construct(
        protected readonly ExtConf $extConf,
        protected readonly RichTextFormElementConfigurator $richTextFormElementConfigurator,
        protected readonly ManagementAccessService $managementAccessService,
    ) {}

    /**
     * Build a form definition, depending on some configuration.
     *
     * @throws RenderingException
     */
    public function build(
        array $configuration,
        ?string $prototypeName = null,
        ?ServerRequestInterface $request = null,
    ): FormDefinition {
        $this->addEventUidToFormAction($configuration, $request);
        $this->modifyEmailFinisherConfiguration($configuration);
        $configuration = $this->richTextFormElementConfigurator->configure($configuration, $request);

        return parent::build($configuration, $prototypeName, $request);
    }

    /**
     * The event to edit is part of the URI of the form. Keep it for the
     * form action. It is the same UID, which is checked and stored.
     */
    protected function addEventUidToFormAction(array &$configuration, ?ServerRequestInterface $request): void
    {
        $eventUid = $request instanceof ServerRequestInterface ? $this->managementAccessService->getEventUid($request) : 0;
        if (
            $eventUid > 0
            && !isset($configuration['renderingOptions']['additionalParams']['tx_events2_management']['event'])
        ) {
            $configuration['renderingOptions']['additionalParams']['tx_events2_management']['event'] = $eventUid;
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
