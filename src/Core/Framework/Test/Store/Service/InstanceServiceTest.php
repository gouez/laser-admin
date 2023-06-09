<?php declare(strict_types=1);

namespace Laser\Core\Framework\Test\Store\Service;

use PHPUnit\Framework\TestCase;
use Laser\Core\Framework\Store\Services\InstanceService;
use Laser\Core\Kernel;

/**
 * @internal
 */
class InstanceServiceTest extends TestCase
{
    public function testItReturnsInstanceIdIfNull(): void
    {
        $instanceService = new InstanceService(
            '6.4.0.0',
            null
        );

        static::assertNull($instanceService->getInstanceId());
    }

    public function testItReturnsInstanceIdIfSet(): void
    {
        $instanceService = new InstanceService(
            '6.4.0.0',
            'i-am-unique'
        );

        static::assertEquals('i-am-unique', $instanceService->getInstanceId());
    }

    public function testItReturnsSpecificLaserVersion(): void
    {
        $instanceService = new InstanceService(
            '6.1.0.0',
            null
        );

        static::assertEquals('6.1.0.0', $instanceService->getLaserVersion());
    }

    public function testItReturnsLaserVersionStringIfVersionIsDeveloperVersion(): void
    {
        $instanceService = new InstanceService(
            Kernel::SHOPWARE_FALLBACK_VERSION,
            null
        );

        static::assertEquals('___VERSION___', $instanceService->getLaserVersion());
    }
}
