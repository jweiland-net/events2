<?php

declare(strict_types=1);

namespace MyVendor\MySitepackage\EventListener;

use JWeiland\Events2\Event\ModifyRichTextSanitizerConfigurationEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

#[AsEventListener(identifier: 'my-sitepackage/allow-heading-4')]
final class AllowHeading4EventListener
{
    public function __invoke(ModifyRichTextSanitizerConfigurationEvent $event): void
    {
        if ($event->getTable() !== 'tx_events2_domain_model_event'
            || $event->getColumn() !== 'detail_information'
        ) {
            return;
        }

        // Keep <h4> instead of converting it to <p>
        $event->setAllowedTags([...$event->getAllowedTags(), 'h4']);
    }
}
