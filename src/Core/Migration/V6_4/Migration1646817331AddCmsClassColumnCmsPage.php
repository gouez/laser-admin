<?php declare(strict_types=1);

namespace Laser\Core\Migration\V6_4;

use Doctrine\DBAL\Connection;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('core')]
class Migration1646817331AddCmsClassColumnCmsPage extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1646817331;
    }

    public function update(Connection $connection): void
    {
        $columns = array_column($connection->fetchAllAssociative('SHOW COLUMNS FROM `cms_page`'), 'Field');

        // only execute when the column does not exist
        if (!\in_array('cms_page', $columns, true)) {
            $connection->executeStatement('ALTER TABLE `cms_page` ADD `css_class` VARCHAR(255) NULL AFTER `locked`;');
        }
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
