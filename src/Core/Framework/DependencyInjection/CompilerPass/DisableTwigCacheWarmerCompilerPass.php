<?php declare(strict_types=1);

namespace Laser\Core\Framework\DependencyInjection\CompilerPass;

use Laser\Core\Framework\Log\Package;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[Package('core')]
class DisableTwigCacheWarmerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $container->removeDefinition('twig.template_cache_warmer');
    }
}
