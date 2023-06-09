<?php declare(strict_types=1);

namespace Laser\Core\Migration\V6_3;

use Doctrine\DBAL\Connection;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('core')]
class Migration1556809270AddAverageRatingToProduct extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1556809270;
    }

    public function update(Connection $connection): void
    {
        // implement update
        $connection->executeStatement('ALTER TABLE `product` ADD `rating_average` float NULL;');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
