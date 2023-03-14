<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Customer\Rule;

use Laser\Core\Checkout\CheckoutRuleScope;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Rule\Rule;
use Laser\Core\Framework\Rule\RuleConfig;
use Laser\Core\Framework\Rule\RuleScope;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Type;

#[Package('business-ops')]
class DifferentAddressesRule extends Rule
{
    final public const RULE_NAME = 'customerDifferentAddresses';

    /**
     * @var bool
     */
    protected $isDifferent;

    /**
     * @internal
     */
    public function __construct(bool $isDifferent = true)
    {
        parent::__construct();
        $this->isDifferent = $isDifferent;
    }

    public function match(RuleScope $scope): bool
    {
        if (!$scope instanceof CheckoutRuleScope) {
            return false;
        }

        if (!$customer = $scope->getSalesChannelContext()->getCustomer()) {
            return false;
        }

        if (!$billingAddress = $customer->getActiveBillingAddress()) {
            return false;
        }

        if (!$shippingAddress = $customer->getActiveShippingAddress()) {
            return false;
        }

        if ($this->isDifferent) {
            return $billingAddress->getId() !== $shippingAddress->getId();
        }

        return $billingAddress->getId() === $shippingAddress->getId();
    }

    public function getConstraints(): array
    {
        return [
            'isDifferent' => [new NotNull(), new Type('bool')],
        ];
    }

    public function getConfig(): RuleConfig
    {
        return (new RuleConfig())
            ->booleanField('isDifferent');
    }
}