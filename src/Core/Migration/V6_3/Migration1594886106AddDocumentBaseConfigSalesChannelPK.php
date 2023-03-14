<?php declare(strict_types=1);

namespace Laser\Core\Migration\V6_3;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('core')]
class Migration1594886106AddDocumentBaseConfigSalesChannelPK extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1594886106;
    }

    public function update(Connection $connection): void
    {
        try {
            $connection->executeStatement('
                ALTER TABLE `document_base_config_sales_channel`
                ADD PRIMARY KEY (`id`);
            ');
        } catch (Exception) {
            // PK already exists
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // nothing
    }
}
