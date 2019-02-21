<?php
declare(strict_types = 1);
namespace JWeiland\Events2\Routing\Aspect;

/*
 * This file is part of the events2 project.
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

use TYPO3\CMS\Core\Routing\Aspect\StaticMappableAspectInterface;

/**
 * Mapper to map a timestamp to a formatted value and back to a timestamp.
 *
 * routeEnhancers:
 *   Events2ShowPlugin:
 *     type: Extbase
 *     extension: Events2
 *     plugin: Events
 *     routes:
 *       -
 *         routePath: '/show/{date}/{event_title}'
 *         _controller: 'Day::show'
 *         _arguments:
 *           date: timestamp
 *           event_title: event
 *     requirements:
 *       date: '\d+'
 *       event_title: '^[a-zA-Z0-9]+\-[0-9]+$'
 *     defaultController: 'Day::show'
 *     aspects:
 *       date:
 *         type: TimestampMapper
 *         format: 'Y-m-d_Hi'
 *       event_title:
 *         type: PersistedPatternMapper
 *         tableName: 'tx_events2_domain_model_event'
 *         routeFieldPattern: '^(?P<title>.+)-(?P<uid>\d+)$'
 *         routeFieldResult: '{title}-{uid}'
 */
class TimestampMapper implements StaticMappableAspectInterface
{
    /**
     * @var array
     */
    protected $settings;

    /**
     * @param array $settings
     * @throws \InvalidArgumentException
     */
    public function __construct(array $settings)
    {
        if (
            !array_key_exists('format', $settings)
            || empty($settings['format'])
        ) {
            throw new \InvalidArgumentException('format must be set', 1550748662);
        }

        $date = new \DateTime('now');
        if (empty($date->format($settings['format']))) {
            throw new \InvalidArgumentException('format must be valid DateTime value', 1550748750);
        }

        $this->settings = $settings;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(string $value): ?string
    {
        $date = new \DateTime(date('c', (int)$value));
        if (!$date instanceof \DateTime) {
            return null;
        } else {
            return $date->format($this->settings['format']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(string $value): ?string
    {
        $date = \DateTime::createFromFormat($this->settings['format'], $value);
        if (!$date instanceof \DateTime) {
            return null;
        } else {
            return $date->format('U');
        }
    }
}
