<?php declare(strict_types=1);

namespace Laser\Core\Framework\Test\Webhook\_fixtures\BusinessEvents;

/**
 * @internal
 */
interface BusinessEventEncoderTestInterface
{
    public function getEncodeValues(string $laserVersion): array;
}
