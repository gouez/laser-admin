<?php declare(strict_types=1);

namespace Laser\Core\Migration\V6_4;

use Doctrine\DBAL\Connection;
use Laser\Core\Content\ProductExport\ScheduledTask\ProductExportGenerateTask;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('core')]
class Migration1633347511ChangeProductExportInterval extends MigrationStep
{
    private const OLD_INTERVAL = 86400;

    public function getCreationTimestamp(): int
    {
        return 1633347511;
    }

    public function update(Connection $connection): void
    {
        $connection->update(
            'scheduled_task',
            [
                'run_interval' => ProductExportGenerateTask::getDefaultInterval(),
            ],
            [
                'run_interval' => self::OLD_INTERVAL,
                'name' => ProductExportGenerateTask::getTaskName(),
            ]
        );
    }

    public function updateDestructive(Connection $connection): void
    {
        // nth
    }
}