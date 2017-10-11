<?php

namespace JWeiland\Events2\Hooks;

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
use DmitryDulepov\Realurl\Configuration\AutomaticConfigurator;

/**
 * Class RealUrl
 *
 * @package JWeiland\Events2\Hooks
 */
class RealUrlAutoConfiguration
{
    /**
     * Generates additional RealURL configuration and merges it with provided configuration
     *
     * @param array $parameters
     * @param AutomaticConfigurator $parentObject
     *
     * @return array Updated configuration
     */
    public function addEvents2Config(array $parameters, AutomaticConfigurator $parentObject)
    {
        return array_merge_recursive($parameters['config'], array(
            'fileName' => array(
                'defaultToHTMLsuffixOnPrev' => true,
            ),
            'postVarSets' => array(
                '_DEFAULT' => array(
                    'event' => array(
                        0 => array(
                            'GETvar' => 'tx_events2_events[controller]',
                            'valueMap' => array(
                                'd' => 'Day',
                                'e' => 'Event',
                                'l' => 'Location',
                                'v' => 'Video',
                                'a' => 'Ajax',
                            ),
                            'noMatch' => 'bypass'
                        ),
                        1 => array(
                            'GETvar' => 'tx_events2_events[action]',
                            'valueMap' => array(
                                'l' => 'list',
                                'll' => 'listLatest',
                                'lt' => 'listToday',
                                'lw' => 'listWeek',
                                'lr' => 'listRange',
                                'lsr' => 'listSearchResults',
                                'lme' => 'listMyEvents',
                                'n' => 'new',
                                'c' => 'create',
                                'e' => 'edit',
                                'u' => 'update',
                                'd' => 'delete',
                                'a' => 'activate',
                                's' => 'show',
                                'st' => 'showByTimestamp',
                                'cao' => 'callAjaxObject',
                            ),
                            'noMatch' => 'bypass'
                        ),
                    ),
                    'ts' => array(
                        0 => array(
                            'GETvar' => 'tx_events2_events[timestamp]',
                            'userFunc' => 'JWeiland\\Events2\\RealUrl\\TimestampMapping->main',
                            'dateFormat' => 'Y-m-d',
                            'timeFormat' => 'Hi',
                        )
                    ),
                    't' => array(
                        0 => array(
                            'GETvar' => 'tx_events2_events[event]',
                            'lookUpTable' => array(
                                'table' => 'tx_events2_domain_model_event',
                                'id_field' => 'uid',
                                'alias_field' => 'CONCAT(title, \'-\', uid)',
                                'useUniqueCache' => 1,
                                'useUniqueCache_conf' => array(
                                    'strtolower' => 1,
                                    'spaceCharacter' => '-',
                                ),
                            ),
                        ),
                    ),
                    'eventLocation' => array(
                        0 => array(
                            'GETvar' => 'tx_events2_events[location]',
                            'lookUpTable' => array(
                                'table' => 'tx_events2_domain_model_location',
                                'id_field' => 'uid',
                                'alias_field' => 'CONCAT(location, \'-\', uid)',
                                'useUniqueCache' => 1,
                                'useUniqueCache_conf' => array(
                                    'strtolower' => 1,
                                    'spaceCharacter' => '-',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ));
    }
}
