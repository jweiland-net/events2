<?php

declare(strict_types=1);

return static function (
    \Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $container,
    \Symfony\Component\DependencyInjection\ContainerBuilder $containerBuilder
) {
    $typo3Version = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \TYPO3\CMS\Core\Information\Typo3Version::class
    );

    // As IndexerHook.php is incompatible with an interface of solr 11.2 we have to exclude both files
    // from "resource" in Services.yaml and load/configure the file individual here.
    // We can't use EMU::isLoaded() as PackageManager is not loaded until now. Using class_exists()
    if (interface_exists(\ApacheSolrForTypo3\Solr\IndexQueue\PageIndexerDocumentsModifier::class)) {
        if (version_compare($typo3Version->getBranch(), '11.4', '>=')) {
            $indexerHookClassName = \JWeiland\Events2\Hooks\Solr\IndexerHook::class;
        } else {
            $indexerHookClassName = \JWeiland\Events2\Hooks\Solr\IndexerHook104::class;
        }

        $indexerHookReflection = $containerBuilder->getReflectionClass($indexerHookClassName);
        if ($indexerHookReflection instanceof ReflectionClass) {
            $indexerHookDefinition = new \Symfony\Component\DependencyInjection\Definition($indexerHookClassName);
            $indexerHookDefinition
                ->setAutowired(true)
                ->setAutoconfigured(true)
                ->setPublic(true);

            $containerBuilder
                ->addResource(new \Symfony\Component\Config\Resource\FileResource($indexerHookReflection->getFileName()))
                ->setDefinition($indexerHookClassName, $indexerHookDefinition);
        }
    }
};
