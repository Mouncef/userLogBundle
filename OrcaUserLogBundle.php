<?php

namespace Orca\UserLogBundle;

use Orca\UserLogBundle\DependencyInjection\UpdateDataBasePass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OrcaUserLogBundle extends Bundle
{

    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new UpdateDataBasePass(), PassConfig::TYPE_AFTER_REMOVING);

    }
}
