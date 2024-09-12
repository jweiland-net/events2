<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use ApacheSolrForTypo3\Solr\IndexQueue\PageIndexerDocumentsModifier;
use JWeiland\Events2\Hooks\Solr\IndexerHook;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (
    ContainerConfigurator $container,
    ContainerBuilder $containerBuilder,
) {
    // As IndexerHook.php is incompatible with an interface of solr 11.2 we have to exclude both files
    // from "resource" in Services.yaml and load/configure the file individual here.
    // We can't use EMU::isLoaded() as PackageManager is not loaded until now. Using class_exists()
    if (interface_exists(PageIndexerDocumentsModifier::class)) {
        $indexerHookClassName = IndexerHook::class;

        $indexerHookReflection = $containerBuilder->getReflectionClass($indexerHookClassName);
        if ($indexerHookReflection instanceof ReflectionClass) {
            $indexerHookDefinition = new Definition($indexerHookClassName);
            $indexerHookDefinition
                ->setAutowired(true)
                ->setAutoconfigured(true)
                ->setPublic(true);

            $containerBuilder
                ->addResource(new FileResource($indexerHookReflection->getFileName()))
                ->setDefinition($indexerHookClassName, $indexerHookDefinition);
        }
    }
};
