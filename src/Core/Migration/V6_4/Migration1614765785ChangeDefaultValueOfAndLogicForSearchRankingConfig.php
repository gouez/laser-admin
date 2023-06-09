<?php declare(strict_types=1);

namespace Laser\Core\Migration\V6_4;

use Doctrine\DBAL\Connection;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('core')]
class Migration1614765785ChangeDefaultValueOfAndLogicForSearchRankingConfig extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1614765785;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('UPDATE product_search_config SET and_logic = 0');
        // implement update
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
