<?php

namespace JWeiland\Events2\RealUrl;

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

/**
 * Class TimestampMapping
 *
 */
class TimestampMapping
{
    /**
     * Map timestamp to readable date
     *
     * @param array $parameters
     * @param object $ref
     *
     * @return string
     */
    public function main(array $parameters, $ref)
    {
        if ($parameters['decodeAlias']) {
            return $this->alias2id($parameters);
        } else {
            return $this->id2alias($parameters);
        }
    }

    /**
     * Map ID to Alias name
     *
     * @param array $parameters
     *
     * @return string
     */
    protected function id2alias(array $parameters)
    {
        if (preg_match('/[0-9]{9,10}/', $parameters['value'])) {
            $date = new \DateTime(date('Y-m-d H:i:s', $parameters['value']));
            if ($date instanceof \DateTime) {
                $parameters['pathParts'][] = rawurlencode($date->format($parameters['setup']['dateFormat']));
                return $date->format($parameters['setup']['timeFormat']);
            }
        }
        return $parameters['value'];
    }

    /**
     * Map alias back to ID
     * This method will only be called if there is no cache entry for this URI
     *
     * @param array $parameters
     * @return string
     */
    protected function alias2id(array $parameters)
    {
        // We have to merge two parts of the URL to one entry.
        // .../[date]/[time]/... ==> timestamp
        // [date] is saved in $parameters['value']
        // $parameters['pathParts'] contains all following parts. The first part of pathParts
        // should be the time (0000 or 1245, ...)
        if (!count($parameters['pathParts']) || empty($parameters['value'])) {
            return 0;
        }

        reset($parameters['pathParts']);
        if (!preg_match('/[0-9]{4,4}/', current($parameters['pathParts']))) {
            return 0;
        }

        $date = \DateTime::createFromFormat(
            $parameters['setup']['dateFormat'] . $parameters['setup']['timeFormat'],
            $parameters['value'] . array_shift($parameters['pathParts'])
        );
        if ($date instanceof \DateTime) {
            return $date->format('U');
        }

        return $parameters['value'];
    }
}
