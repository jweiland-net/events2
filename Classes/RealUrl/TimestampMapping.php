<?php

namespace JWeiland\Events2\RealUrl;

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

/**
 * Class TimestampMapping
 *
 * @package JWeiland\Events2\RealUrl
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
    public function main(array $parameters, $ref)    {
        if ($parameters['decodeAlias'])     {
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
    protected function id2alias(array $parameters) {
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
     *
     * @param array $parameters
     *
     * @return string
     */
    protected function alias2id(array $parameters) {
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
