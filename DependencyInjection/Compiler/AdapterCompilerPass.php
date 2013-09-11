<?php
namespace SRozeIO\SocialShareBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class AdapterCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('srozeio.social_share.builder')) {
            return;
        }

        $definition = $container->getDefinition('srozeio.social_share.builder');
        $taggedServices = $container->findTaggedServiceIds('srozeio.social_share.adapter');
        
        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('addAdapter', array(new Reference($id)));
        }
    }
}
