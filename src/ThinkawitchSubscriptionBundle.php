<?php

namespace Thinkawitch\SubscriptionBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Thinkawitch\SubscriptionBundle\DependencyInjection\Compiler\ServicesCompilerPass;
use Thinkawitch\SubscriptionBundle\DependencyInjection\Compiler\SubscriptionStrategyCompilerPass;

class ThinkawitchSubscriptionBundle extends Bundle
{
    const COMMAND_NAMESPACE = 'thinkawitch:subscription';

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new SubscriptionStrategyCompilerPass());
        $container->addCompilerPass(new ServicesCompilerPass());
    }
}
