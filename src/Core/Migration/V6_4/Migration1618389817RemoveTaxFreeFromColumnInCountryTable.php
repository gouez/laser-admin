<?php declare(strict_types=1);

namespace Laser\Core\Migration\V6_4;

use Doctrine\DBAL\Connection;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('core')]
class Migration1618389817RemoveTaxFreeFromColumnInCountryTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1618389817;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `country` DROP COLUMN `tax_free_from`');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
