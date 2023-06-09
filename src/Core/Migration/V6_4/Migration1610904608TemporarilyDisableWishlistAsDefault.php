<?php declare(strict_types=1);

namespace Laser\Core\Migration\V6_4;

use Doctrine\DBAL\Connection;
use Laser\Core\Defaults;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('core')]
class Migration1610904608TemporarilyDisableWishlistAsDefault extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1610904608;
    }

    public function update(Connection $connection): void
    {
        $configId = $connection->fetchOne('SELECT id FROM system_config WHERE configuration_key = :key', [
            'key' => 'core.cart.wishlistEnabled',
        ]);

        if (!$configId) {
            return;
        }

        $connection->update('system_config', [
            'configuration_value' => json_encode(['_value' => false]),
            'updated_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ], [
            'id' => $configId,
        ]);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
