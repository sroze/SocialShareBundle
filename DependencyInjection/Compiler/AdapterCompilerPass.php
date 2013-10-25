<?php
namespace SRIO\SocialShareBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class AdapterCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('srio.social_share.builder')) {
            return;
        }

        $definition = $container->getDefinition('srio.social_share.builder');
        $taggedServices = $container->findTaggedServiceIds('srio.social_share.adapter');
        
        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('addAdapter', array(new Reference($id)));
        }
    }
}
