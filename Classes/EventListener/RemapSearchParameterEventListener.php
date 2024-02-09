<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\EventListener;

use JWeiland\Events2\Event\PreProcessControllerActionEvent;

/**
 * Event and Search plugin are two different plugins with different plugin namespaces (events2_list, events2_search).
 * If you submit search form "events2_search" will be used, but as plugin events2_list will show the results, it
 * will not react on these foreign requests. That why we have switches the plugin namespace of search plugin to
 * "events2_list" in TypoScript. Now all search results are visible, but all search values in form gets lost. With
 * this workaround we fetch the events2_list request and map back the form values to this plugin.
 */
class RemapSearchParameterEventListener extends AbstractControllerEventListener
{
    protected array $allowedControllerActions = [
        'Search' => [
            'show'
        ]
    ];

    public function __invoke(PreProcessControllerActionEvent $controllerActionEvent): void
    {
        if ($this->isValidRequest($controllerActionEvent)) {
            $foreignPluginContext = $controllerActionEvent->getRequest()->getParsedBody()['tx_events2_list'];
            if (isset($foreignPluginContext['search'])) {
                $search = $foreignPluginContext['search'];
                if (!is_array($search)) {
                    return;
                }

                if (empty($search)) {
                    return;
                }

                $controllerActionEvent->getRequest()->setArgument('search', $search);
            }
        }
    }
}
