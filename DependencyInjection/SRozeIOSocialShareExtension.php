<?php

namespace SRIO\SocialShareBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Reference;

use Symfony\Component\DependencyInjection\DefinitionDecorator;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SRozeIOSocialShareExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
        
        // Configure adapters
        foreach ($config['adapters'] as $name => $options) {
            $this->createAdapter($container, $name, $options);
        }
    }
    
    /**
     * Create an adapter.
     * 
     * @param string $name
     * @param array  $options
     */
    protected function createAdapter ($container, $name, array $options)
    {
        $type = $options['type'];
        $definition = new DefinitionDecorator('srio.social_share.abstract_adapter.'.$type);
        $id = 'srio.social_share.adapter.'.$name;
        
        $container->setDefinition($id, $definition);
        $definition
            ->replaceArgument(0, $name)
            ->replaceArgument(1, $options)
        ;
        
        // Add adapter to builder
        $definition = $container->getDefinition('srio.social_share.builder');
        $definition->addMethodCall('addAdapter', array(new Reference($id)));
    }
}
