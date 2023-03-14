<?php declare(strict_types=1);

namespace Laser\Core\Installer\Database;

use Doctrine\DBAL\Connection;
use Psr\Log\NullLogger;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Migration\MigrationCollectionLoader;
use Laser\Core\Framework\Migration\MigrationRuntime;
use Laser\Core\Framework\Migration\MigrationSource;

/**
 * @internal
 */
#[Package('core')]
class MigrationCollectionFactory
{
    public function __construct(private readonly string $projectDir)
    {
    }

    public function getMigrationCollectionLoader(Connection $connection): MigrationCollectionLoader
    {
        return new MigrationCollectionLoader(
            $connection,
            new MigrationRuntime($connection, new NullLogger()),
            $this->collect()
        );
    }

    /**
     * @return list<MigrationSource>
     */
    private function collect(): array
    {
        return [
            new MigrationSource('core', []),
            self::createMigrationSource('V6_3'),
            self::createMigrationSource('V6_4'),
            self::createMigrationSource('V6_5'),
            self::createMigrationSource('V6_6'),
        ];
    }

    private function createMigrationSource(string $version): MigrationSource
    {
        if (file_exists($this->projectDir . '/platform/src/Core/schema.sql')) {
            $coreBasePath = $this->projectDir . '/platform/src/Core';
            $storefrontBasePath = $this->projectDir . '/platform/src/Storefront';
            $adminBasePath = $this->projectDir . '/platform/src/Administration';
        } elseif (file_exists($this->projectDir . '/src/Core/schema.sql')) {
            $coreBasePath = $this->projectDir . '/src/Core';
            $storefrontBasePath = $this->projectDir . '/src/Storefront';
            $adminBasePath = $this->projectDir . '/src/Administration';
        } else {
            $coreBasePath = $this->projectDir . '/vendor/laser/core';
            $storefrontBasePath = $this->projectDir . '/vendor/laser/storefront';
            $adminBasePath = $this->projectDir . '/vendor/laser/administration';
        }

        $hasStorefrontMigrations = is_dir($storefrontBasePath);
        $hasAdminMigrations = is_dir($adminBasePath);

        $source = new MigrationSource('core.' . $version, [
            sprintf('%s/Migration/%s', $coreBasePath, $version) => sprintf('Laser\\Core\\Migration\\%s', $version),
        ]);

        if ($hasStorefrontMigrations) {
            $source->addDirectory(sprintf('%s/Migration/%s', $storefrontBasePath, $version), sprintf('Laser\\Storefront\\Migration\\%s', $version));
        }

        if ($hasAdminMigrations) {
            $source->addDirectory(sprintf('%s/Migration/%s', $adminBasePath, $version), sprintf('Laser\\Administration\\Migration\\%s', $version));
        }

        return $source;
    }
}