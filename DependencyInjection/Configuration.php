<?php

namespace SRozeIO\SocialShareBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('srozeio_social_share');

        $rootNode->children()
            ->arrayNode('adapters')
                ->prototype('array')
                ->children()
                    ->scalarNode('type')->isRequired()->cannotBeEmpty()->end()
                    ->scalarNode('client_id')->isRequired()->cannotBeEmpty()->end()
                    ->scalarNode('client_secret')->isRequired()->cannotBeEmpty()->end()
                    
                    ->scalarNode('scope')->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
