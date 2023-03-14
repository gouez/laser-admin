<?php declare(strict_types=1);

namespace Laser\Core\Framework\Test\Plugin;

use Composer\IO\NullIO;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Laser\Core\Framework\Context;
use Laser\Core\Framework\DataAbstractionLayer\EntityRepository;
use Laser\Core\Framework\Migration\MigrationCollection;
use Laser\Core\Framework\Migration\MigrationCollectionLoader;
use Laser\Core\Framework\Migration\MigrationSource;
use Laser\Core\Framework\Plugin\Composer\CommandExecutor;
use Laser\Core\Framework\Plugin\KernelPluginCollection;
use Laser\Core\Framework\Plugin\PluginEntity;
use Laser\Core\Framework\Plugin\PluginLifecycleService;
use Laser\Core\Framework\Plugin\PluginService;
use Laser\Core\Framework\Plugin\Requirement\RequirementsValidator;
use Laser\Core\Framework\Plugin\Util\AssetService;
use Laser\Core\Framework\Plugin\Util\PluginFinder;
use Laser\Core\Framework\Test\Migration\MigrationTestBehaviour;
use Laser\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Laser\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Laser\Core\Framework\Test\TestCaseHelper\ReflectionHelper;
use Laser\Core\Kernel;
use Laser\Core\System\CustomEntity\CustomEntityLifecycleService;
use Laser\Core\System\CustomEntity\Schema\CustomEntityPersister;
use Laser\Core\System\CustomEntity\Schema\CustomEntitySchemaUpdater;
use Laser\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @internal
 *
 * @group slow
 * @group skip-paratest
 */
class PluginLifecycleServiceMigrationTest extends TestCase
{
    use KernelTestBehaviour;
    use PluginTestsHelper;
    use MigrationTestBehaviour;

    private ContainerInterface $container;

    /**
     * @var EntityRepository
     */
    private $pluginRepo;

    private PluginService $pluginService;

    /**
     * @var Connection
     */
    private $connection;

    private PluginLifecycleService $pluginLifecycleService;

    private Context $context;

    public static function tearDownAfterClass(): void
    {
        $connection = Kernel::getConnection();

        $connection->executeStatement('DELETE FROM migration WHERE `class` LIKE "SwagManualMigrationTest%"');
        $connection->executeStatement('DELETE FROM plugin');

        KernelLifecycleManager::bootKernel();
    }

    protected function setUp(): void
    {
        // force kernel boot
        KernelLifecycleManager::bootKernel();

        $this->container = $this->getContainer();
        $this->pluginRepo = $this->container->get('plugin.repository');
        $this->connection = $this->container->get(Connection::class);
        $this->pluginLifecycleService = $this->createPluginLifecycleService();
        $this->context = Context::createDefaultContext();

        $this->pluginService = $this->createPluginService(
            __DIR__ . '/_fixture/plugins',
            $this->container->getParameter('kernel.project_dir'),
            $this->pluginRepo,
            $this->container->get('language.repository'),
            $this->container->get(PluginFinder::class)
        );

        $this->addTestPluginToKernel(
            __DIR__ . '/_fixture/plugins/SwagManualMigrationTest',
            'SwagManualMigrationTest'
        );
        $this->requireMigrationFiles();

        $this->pluginService->refreshPlugins($this->context, new NullIO());
        $this->connection->executeStatement('DELETE FROM plugin WHERE `name` = "SwagTest"');
    }

    public function testInstall(): MigrationCollection
    {
        static::assertSame(0, $this->connection->getTransactionNestingLevel());

        $migrationPlugin = $this->getMigrationTestPlugin();
        static::assertNull($migrationPlugin->getInstalledAt());

        $this->pluginLifecycleService->installPlugin($migrationPlugin, $this->context);
        $migrationCollection = $this->getMigrationCollection('SwagManualMigrationTest');
        $this->assertMigrationState($migrationCollection, 4, 1);

        return $migrationCollection;
    }

    /**
     * @depends testInstall
     */
    public function testActivate(MigrationCollection $migrationCollection): MigrationCollection
    {
        $migrationPlugin = $this->getMigrationTestPlugin();
        $this->pluginLifecycleService->activatePlugin($migrationPlugin, $this->context);
        $this->assertMigrationState($migrationCollection, 4, 2);

        return $migrationCollection;
    }

    /**
     * @depends testActivate
     */
    public function testUpdate(MigrationCollection $migrationCollection): MigrationCollection
    {
        $migrationPlugin = $this->getMigrationTestPlugin();
        $this->pluginLifecycleService->updatePlugin($migrationPlugin, $this->context);
        $this->assertMigrationState($migrationCollection, 4, 3, 1);

        return $migrationCollection;
    }

    /**
     * @depends testUpdate
     */
    public function testDeactivate(MigrationCollection $migrationCollection): MigrationCollection
    {
        $migrationPlugin = $this->getMigrationTestPlugin();
        $this->pluginLifecycleService->deactivatePlugin($migrationPlugin, $this->context);
        $this->assertMigrationState($migrationCollection, 4, 3, 1);

        return $migrationCollection;
    }

    /**
     * @depends testDeactivate
     */
    public function testUninstallKeepUserData(MigrationCollection $migrationCollection): void
    {
        $migrationPlugin = $this->getMigrationTestPlugin();
        $this->pluginLifecycleService->uninstallPlugin($migrationPlugin, $this->context, true);
        $this->assertMigrationCount($migrationCollection, 4);
    }

    private function assertMigrationCount(MigrationCollection $migrationCollection, int $expectedCount): void
    {
        $connection = $this->getContainer()->get(Connection::class);

        /** @var MigrationSource $migrationSource */
        $migrationSource = ReflectionHelper::getPropertyValue($migrationCollection, 'migrationSource');

        $dbMigrations = $connection
            ->fetchAllAssociative(
                'SELECT * FROM `migration` WHERE `class` REGEXP :pattern ORDER BY `creation_timestamp`',
                ['pattern' => $migrationSource->getNamespacePattern()]
            );

        TestCase::assertCount($expectedCount, $dbMigrations);
    }

    private function createPluginLifecycleService(): PluginLifecycleService
    {
        return new PluginLifecycleService(
            $this->pluginRepo,
            $this->container->get('event_dispatcher'),
            $this->container->get(KernelPluginCollection::class),
            $this->container->get('service_container'),
            $this->container->get(MigrationCollectionLoader::class),
            $this->container->get(AssetService::class),
            $this->container->get(CommandExecutor::class),
            $this->container->get(RequirementsValidator::class),
            $this->container->get('cache.messenger.restart_workers_signal'),
            Kernel::SHOPWARE_FALLBACK_VERSION,
            $this->container->get(SystemConfigService::class),
            $this->container->get(CustomEntityPersister::class),
            $this->container->get(CustomEntitySchemaUpdater::class),
            $this->container->get(CustomEntityLifecycleService::class),
        );
    }

    private function getMigrationTestPlugin(): PluginEntity
    {
        return $this->pluginService
            ->getPluginByName('SwagManualMigrationTest', $this->context);
    }

    private function requireMigrationFiles(): void
    {
        require_once __DIR__ . '/_fixture/plugins/SwagManualMigrationTest/src/Migration/Migration1.php';
        require_once __DIR__ . '/_fixture/plugins/SwagManualMigrationTest/src/Migration/Migration2.php';
        require_once __DIR__ . '/_fixture/plugins/SwagManualMigrationTest/src/Migration/Migration3.php';
        require_once __DIR__ . '/_fixture/plugins/SwagManualMigrationTest/src/Migration/Migration4.php';
    }
}
