<?php declare(strict_types=1);

namespace Laser\Core\Framework\Test\Cache;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Laser\Core\Framework\Adapter\Cache\CacheClearer;
use Laser\Core\Framework\Plugin\KernelPluginLoader\StaticKernelPluginLoader;
use Laser\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Laser\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Laser\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Laser\Core\Framework\Uuid\Uuid;
use Laser\Core\Kernel;
use Symfony\Component\Finder\Finder;

/**
 * @internal
 *
 * @group skip-paratest
 * @group slow
 */
class CacheClearerTest extends TestCase
{
    use KernelTestBehaviour;
    use DatabaseTransactionBehaviour;

    public function testCleanupOldKernelDirectories(): void
    {
        $classLoader = clone KernelLifecycleManager::getClassLoader();
        KernelLifecycleManager::getClassLoader()->unregister();
        $classLoader->register();

        $original = KernelLifecycleManager::getKernel();

        $oldCacheDirs = [];
        for ($i = 0; $i < 2; ++$i) {
            $class = KernelLifecycleManager::getKernelClass();
            /** @var Kernel $kernel */
            $kernel = new $class(
                'test',
                true,
                new StaticKernelPluginLoader($classLoader),
                Uuid::randomHex(),
                '1.0.0@' . $i . '1eec7b5ea3f0fdbc95d0dd47f3c5bc275da8a33',
                $original->getContainer()->get(Connection::class)
            );

            $kernel->boot();
            $oldCacheDir = $kernel->getCacheDir();
            static::assertFileExists($oldCacheDir);
            $kernel->shutdown();
            $oldCacheDirs[] = $oldCacheDir;
        }
        $oldCacheDirs = array_unique($oldCacheDirs);

        static::assertCount(2, $oldCacheDirs);

        $second = KernelLifecycleManager::getKernel();
        $second->boot();
        static::assertFileExists($second->getCacheDir());

        static::assertNotContains($second->getCacheDir(), $oldCacheDirs);

        $this->getContainer()->get(CacheClearer::class)->clear();

        foreach ($oldCacheDirs as $oldCacheDir) {
            static::assertFileDoesNotExist($oldCacheDir);
        }
    }

    public function testClearContainerCache(): void
    {
        $kernelClass = KernelLifecycleManager::getKernelClass();
        /** @var Kernel $newTestKernel */
        $newTestKernel = new $kernelClass(
            'test',
            true,
            new StaticKernelPluginLoader(KernelLifecycleManager::getClassLoader()),
            Uuid::randomHex(),
            '1.1.1',
            $this->getContainer()->get(Connection::class)
        );

        $newTestKernel->boot();
        $cacheDir = $newTestKernel->getCacheDir();
        $newTestKernel->shutdown();

        $finder = (new Finder())->in($cacheDir)->directories()->name('Container*');
        $containerCaches = [];

        foreach ($finder->getIterator() as $containerPaths) {
            $containerCaches[] = $containerPaths->getRealPath();
        }

        static::assertCount(1, $containerCaches);

        $filesystem = $this->getContainer()->get('filesystem');
        $cacheClearer = new CacheClearer(
            [],
            $this->getContainer()->get('cache_clearer'),
            $filesystem,
            $cacheDir,
            'test',
            $this->getContainer()->get('messenger.bus.laser')
        );

        $cacheClearer->clearContainerCache();

        foreach ($containerCaches as $containerCache) {
            static::assertFileDoesNotExist($containerCache);
        }

        $filesystem->remove($cacheDir);
    }

    public function testUrlGeneratorCacheGetsCleared(): void
    {
        $cacheClearer = $this->getContainer()->get(CacheClearer::class);

        touch(sprintf('%s%sUrlGenerator.php', $this->getKernel()->getCacheDir(), \DIRECTORY_SEPARATOR));
        touch(sprintf('%s%sUrlGenerator.php.meta', $this->getKernel()->getCacheDir(), \DIRECTORY_SEPARATOR));

        $urlGeneratorCacheFileFinder = (new Finder())->in($this->getKernel()->getCacheDir())->files()->name('UrlGenerator.php*');

        static::assertCount(2, $urlGeneratorCacheFileFinder);

        $cacheClearer->clear();

        foreach ($urlGeneratorCacheFileFinder->getIterator() as $generatorFile) {
            static::assertFileDoesNotExist($generatorFile);
        }
    }
}
