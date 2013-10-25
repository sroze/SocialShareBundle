<?php

namespace SRIO\SocialShareBundle;

use SRIO\SocialShareBundle\DependencyInjection\Compiler\AdapterCompilerPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class SRozeIOSocialShareBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AdapterCompilerPass());
    }
}
