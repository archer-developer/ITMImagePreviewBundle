<?php

namespace ITM\ImagePreviewBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('itm_image_preview');

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        $rootNode
            ->children()
            ->variableNode('upload_path')->end()
            ->variableNode('upload_url')->end()
            ->variableNode('public_dir')->defaultValue('web')->end()
            ->arrayNode('entities')
                ->prototype('array')
                    ->beforeNormalization()
                        ->ifArray()
                        ->then(function($v) { return array('bundle' => $v); })
                    ->end()
                    ->children()
                        ->arrayNode('bundle')
                        ->prototype('array')
                            ->beforeNormalization()
                                ->ifArray()
                                ->then(function($v) { return array('entity' => $v); })
                            ->end()
                            ->children()
                                ->arrayNode('entity')
                                ->prototype('array')
                                    ->beforeNormalization()
                                        ->ifArray()
                                        ->then(function($v) { return array('field' => $v); })
                                    ->end()
                                    ->children()
                                        ->arrayNode('field')
                                        ->children()
                                            ->arrayNode('formats')
                                            ->prototype('array')
                                            ->beforeNormalization()
                                                ->ifString()
                                                ->then(function($v) { return array('format' => $v); })
                                            ->end()
                                            ->children()
                                                ->scalarNode('format')
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
