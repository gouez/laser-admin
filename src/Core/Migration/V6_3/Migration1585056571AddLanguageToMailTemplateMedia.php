<?php declare(strict_types=1);

namespace Laser\Core\Migration\V6_3;

use Doctrine\DBAL\Connection;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('core')]
class Migration1585056571AddLanguageToMailTemplateMedia extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1585056571;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(
            <<<'SQL'
            ALTER TABLE `mail_template_media` ADD `language_id` BINARY(16) NULL AFTER `mail_template_id`,
            ADD CONSTRAINT `fk.mail_template_media.language_id` FOREIGN KEY (`language_id`)
             REFERENCES `language` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
SQL
        );
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
