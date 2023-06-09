<?php declare(strict_types=1);

namespace Laser\Core\System\SystemConfig;

use Laser\Core\Framework\Log\Package;

#[Package('system-settings')]
abstract class AbstractSystemConfigLoader
{
    abstract public function getDecorated(): AbstractSystemConfigLoader;

    abstract public function load(?string $salesChannelId): array;
}
