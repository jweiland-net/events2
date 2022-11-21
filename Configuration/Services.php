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
    if (version_compare($typo3Version->getBranch(), '11.4', '>=')) {
        $className = \JWeiland\Events2\Hooks\Solr\IndexerHook::class;
        $absPath = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(
            'EXT:events2/Classes/Hooks/Solr/IndexerHook.php'
        );
    } else {
        $className = \JWeiland\Events2\Hooks\Solr\IndexerHook104::class;
        $absPath = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(
            'EXT:events2/Classes/Hooks/Solr/IndexerHook104.php'
        );
    }
    $indexerHookDefinition = new \Symfony\Component\DependencyInjection\Definition($className);
    $indexerHookDefinition
        ->setAutowired(true)
        ->setAutoconfigured(true)
        ->setPublic(true);

    $containerBuilder
        ->addResource(new \Symfony\Component\Config\Resource\FileResource($absPath))
        ->setDefinition($className, $indexerHookDefinition);
};
