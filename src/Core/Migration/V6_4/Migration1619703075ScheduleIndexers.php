<?php declare(strict_types=1);

namespace Laser\Core\Migration\V6_4;

use Doctrine\DBAL\Connection;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('core')]
class Migration1619703075ScheduleIndexers extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1619703075;
    }

    public function update(Connection $connection): void
    {
        $this->registerIndexer($connection, 'product.indexer');
        $this->registerIndexer($connection, 'product_stream.indexer');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
