<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Shipping\Event;

use Laser\Core\Framework\Adapter\Cache\StoreApiRouteCacheKeyEvent;
use Laser\Core\Framework\Log\Package;

#[Package('checkout')]
class ShippingMethodRouteCacheKeyEvent extends StoreApiRouteCacheKeyEvent
{
}