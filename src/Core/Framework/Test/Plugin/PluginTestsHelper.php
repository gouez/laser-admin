<?php declare(strict_types=1);

namespace Laser\Core\Framework\Test\Plugin;

use Laser\Core\Framework\Context;
use Laser\Core\Framework\DataAbstractionLayer\EntityRepository;
use Laser\Core\Framework\Plugin;
use Laser\Core\Framework\Plugin\Changelog\ChangelogParser;
use Laser\Core\Framework\Plugin\Changelog\ChangelogService;
use Laser\Core\Framework\Plugin\KernelPluginCollection;
use Laser\Core\Framework\Plugin\KernelPluginLoader\KernelPluginLoader;
use Laser\Core\Framework\Plugin\PluginService;
use Laser\Core\Framework\Plugin\Util\PluginFinder;
use Laser\Core\Framework\Plugin\Util\VersionSanitizer;
use SwagTest\SwagTest;
use Symfony\Component\DependencyInjection\ContainerInterface;

trait PluginTestsHelper
{
    protected function createPluginService(
        string $pluginDir,
        string $projectDir,
        EntityRepository $pluginRepo,
        EntityRepository $languageRepo,
        PluginFinder $pluginFinder
    ): PluginService {
        return new PluginService(
            $pluginDir,
            $projectDir,
            $pluginRepo,
            $languageRepo,
            new ChangelogService(new ChangelogParser()),
            $pluginFinder,
            new VersionSanitizer()
        );
    }

    protected function createPlugin(
        EntityRepository $pluginRepo,
        Context $context,
        string $version = SwagTest::PLUGIN_VERSION,
        ?string $installedAt = null
    ): void {
        $pluginRepo->create(
            [
                [
                    'baseClass' => SwagTest::class,
                    'name' => 'SwagTest',
                    'version' => $version,
                    'label' => SwagTest::PLUGIN_LABEL,
                    'installedAt' => $installedAt,
                    'active' => false,
                    'autoload' => [],
                ],
            ],
            $context
        );
    }

    abstract protected static function getContainer(): ContainerInterface;

    private function addTestPluginToKernel(string $testPluginBaseDir, string $pluginName, bool $active = false): void
    {
        require_once $testPluginBaseDir . '/src/' . $pluginName . '.php';

        /** @var KernelPluginCollection $pluginCollection */
        $pluginCollection = $this->getContainer()->get(KernelPluginCollection::class);
        /** @var class-string<Plugin> $class */
        $class = '\\' . $pluginName . '\\' . $pluginName;
        $plugin = new $class($active, $testPluginBaseDir);
        $pluginCollection->add($plugin);

        $this->getContainer()->get(KernelPluginLoader::class)->getPluginInstances()->add($plugin);
    }
}
