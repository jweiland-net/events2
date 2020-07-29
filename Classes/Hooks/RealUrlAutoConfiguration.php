<?php

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace JWeiland\Events2\Hooks;

use DmitryDulepov\Realurl\Configuration\AutomaticConfigurator;
use JWeiland\Events2\RealUrl\TimestampMapping;
use JWeiland\Jwtools2\RealUrl\ConvertTableAliasToId;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Class RealUrl
 *
 */
class RealUrlAutoConfiguration
{
    /**
     * Generates additional RealURL configuration and merges it with provided configuration
     *
     * @param array $parameters
     * @param AutomaticConfigurator $parentObject
     * @return array Updated configuration
     */
    public function addEvents2Config(array $parameters, AutomaticConfigurator $parentObject)
    {
        $realUrlConfiguration = array_merge_recursive(
            $parameters['config'],
            [
                'fileName' => [
                    'defaultToHTMLsuffixOnPrev' => true,
                ],
                'postVarSets' => [
                    '_DEFAULT' => [
                        'event' => [
                            0 => [
                                'GETvar' => 'tx_events2_events[controller]',
                                'valueMap' => [
                                    'd' => 'Day',
                                    'e' => 'Event',
                                    'l' => 'Location',
                                    'v' => 'Video',
                                    'a' => 'Ajax',
                                ],
                                'noMatch' => 'bypass'
                            ],
                            1 => [
                                'GETvar' => 'tx_events2_events[action]',
                                'valueMap' => [
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
                                ],
                                'noMatch' => 'bypass'
                            ],
                        ],
                        'ts' => [
                            0 => [
                                'GETvar' => 'tx_events2_events[timestamp]',
                                'userFunc' => TimestampMapping::class . '->main',
                                'dateFormat' => 'Y-m-d',
                                'timeFormat' => 'Hi',
                            ]
                        ],
                        't' => [
                            0 => [
                                'GETvar' => 'tx_events2_events[event]',
                                'lookUpTable' => [
                                    'table' => 'tx_events2_domain_model_event',
                                    'id_field' => 'uid',
                                    'alias_field' => 'CONCAT(title, \'-\', uid)',
                                    'useUniqueCache' => 1,
                                    'useUniqueCache_conf' => [
                                        'strtolower' => 1,
                                        'spaceCharacter' => '-',
                                    ],
                                ],
                            ],
                        ],
                        'eventLocation' => [
                            0 => [
                                'GETvar' => 'tx_events2_events[location]',
                                'lookUpTable' => [
                                    'table' => 'tx_events2_domain_model_location',
                                    'id_field' => 'uid',
                                    'alias_field' => 'CONCAT(location, \'-\', uid)',
                                    'useUniqueCache' => 1,
                                    'useUniqueCache_conf' => [
                                        'strtolower' => 1,
                                        'spaceCharacter' => '-',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        if (ExtensionManagementUtility::isLoaded('jwtools2')) {
            $userFunc = ConvertTableAliasToId::class . '->convert';
            $realUrlConfiguration['postVarSets']['_DEFAULT']['t'][0]['userFunc'] = $userFunc;
        }

        return $realUrlConfiguration;
    }
}
