<?php

declare(strict_types=1);

namespace MyVendor\MySitepackage\Slug;

use TYPO3\CMS\Core\DataHandling\SlugHelper;

final class EventSlugModifier
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function modify(array $parameters, SlugHelper $slugHelper): string
    {
        $eventType = (string)($parameters['record']['event_type'] ?? '');
        if ($eventType !== 'recurring') {
            return $parameters['slug'];
        }

        // Prefix recurring events like "recurring-yoga-course"
        return 'recurring-' . $parameters['slug'];
    }
}
