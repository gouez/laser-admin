<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Customer\Event;

use Laser\Core\Checkout\Customer\Aggregate\CustomerRecovery\CustomerRecoveryEntity;
use Laser\Core\Framework\Context;
use Laser\Core\Framework\Event\LaserSalesChannelEvent;
use Laser\Core\Framework\Log\Package;
use Laser\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('customer-order')]
class PasswordRecoveryUrlEvent extends Event implements LaserSalesChannelEvent
{
    public function __construct(
        private readonly SalesChannelContext $salesChannelContext,
        private string $recoveryUrl,
        private readonly string $hash,
        private readonly string $storefrontUrl,
        private readonly CustomerRecoveryEntity $customerRecovery
    ) {
    }

    public function getRecoveryUrl(): string
    {
        return $this->recoveryUrl;
    }

    public function setRecoveryUrl(string $recoveryUrl): void
    {
        $this->recoveryUrl = $recoveryUrl;
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }

    public function getContext(): Context
    {
        return $this->salesChannelContext->getContext();
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannelContext->getSalesChannel()->getId();
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getStorefrontUrl(): string
    {
        return $this->storefrontUrl;
    }

    public function getCustomerRecovery(): CustomerRecoveryEntity
    {
        return $this->customerRecovery;
    }
}
