<?php declare(strict_types=1);

namespace Laser\Core\DevOps\StaticAnalyze;

use Laser\Core\Framework\Log\Package;
use Laser\Core\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
#[Package('core')]
class StaticAnalyzeKernel extends Kernel
{
    public function getCacheDir(): string
    {
        return sprintf(
            '%s/var/cache/%s',
            $this->getProjectDir(),
            $this->getEnvironment()
        );
    }

    /**
     * @see https://github.com/phpstan/phpstan/issues/6075
     */
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        parent::configureContainer($container, $loader);
        $container->setParameter('container.dumper.inline_class_loader', false);
    }
}
