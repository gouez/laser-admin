<?php declare(strict_types=1);

namespace Laser\Core\Migration\V6_4;

use Doctrine\DBAL\Connection;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('core')]
class Migration1610337444AddSlotConfigToProductTranslationTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1610337444;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<'SQL'
ALTER TABLE `product_translation`
    ADD COLUMN `slot_config` JSON AFTER `custom_fields`,
    ADD CONSTRAINT `json.product_translation.slot_config` CHECK (JSON_VALID(`slot_config`))
SQL;
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
