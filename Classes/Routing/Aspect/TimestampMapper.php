<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Routing\Aspect;

use TYPO3\CMS\Core\Routing\Aspect\StaticMappableAspectInterface;

/*
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
 *       date: '\d{4,4}-\d{2,2}-\d{2,2}_\d{4,4}'
 *       event_title: '^[a-zA-Z0-9\-]+$'
 *     defaultController: 'Day::show'
 *     aspects:
 *       date:
 *         type: TimestampMapper
 *         format: 'Y-m-d_Hi'
 *       event_title:
 *         type: PersistedAliasMapper
 *         tableName: tx_events2_domain_model_event
 *         routeFieldName: path_segment
 */
class TimestampMapper implements StaticMappableAspectInterface
{
    protected array $settings;

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

        return $date->format($this->settings['format']);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(string $value): ?string
    {
        $date = \DateTime::createFromFormat($this->settings['format'], $value);
        if (!$date instanceof \DateTime) {
            return null;
        }

        return $date->format('U');
    }
}
