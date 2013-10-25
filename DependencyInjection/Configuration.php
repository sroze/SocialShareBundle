<?php

namespace SRIO\SocialShareBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('srio_social_share');

        $rootNode->children()
            ->arrayNode('adapters')
                ->prototype('array')
                ->children()
                    ->scalarNode('type')->isRequired()->cannotBeEmpty()->end()
                    ->scalarNode('client_id')->isRequired()->cannotBeEmpty()->end()
                    ->scalarNode('client_secret')->isRequired()->cannotBeEmpty()->end()
                    
                    ->scalarNode('request_visible_actions')->end()
                    ->scalarNode('approval_prompt')->end()
                    ->scalarNode('scope')->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
