<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Customer\Event;

use Laser\Core\Framework\Log\Package;

#[Package('customer-order')]
class GuestCustomerRegisterEvent extends CustomerRegisterEvent
{
    final public const EVENT_NAME = 'checkout.customer.guest_register';

    public function getName(): string
    {
        return self::EVENT_NAME;
    }
}