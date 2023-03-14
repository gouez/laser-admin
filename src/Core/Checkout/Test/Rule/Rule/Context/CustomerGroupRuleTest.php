<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Test\Rule\Rule\Context;

use PHPUnit\Framework\TestCase;
use Laser\Core\Checkout\Cart\Cart;
use Laser\Core\Checkout\Cart\Rule\CartRuleScope;
use Laser\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Laser\Core\Checkout\Customer\Rule\CustomerGroupRule;
use Laser\Core\Framework\Log\Package;
use Laser\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 */
#[Package('business-ops')]
class CustomerGroupRuleTest extends TestCase
{
    public function testMatch(): void
    {
        $rule = (new CustomerGroupRule())->assign(['customerGroupIds' => ['SWAG-CUSTOMER-GROUP-ID-1']]);

        $cart = new Cart('test');

        $group = new CustomerGroupEntity();
        $group->setId('SWAG-CUSTOMER-GROUP-ID-1');

        $context = $this->createMock(SalesChannelContext::class);

        $context
            ->method('getCurrentCustomerGroup')
            ->willReturn($group);

        static::assertTrue(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testMultipleGroups(): void
    {
        $rule = (new CustomerGroupRule())->assign(['customerGroupIds' => ['SWAG-CUSTOMER-GROUP-ID-2', 'SWAG-CUSTOMER-GROUP-ID-3', 'SWAG-CUSTOMER-GROUP-ID-1']]);

        $cart = new Cart('test');

        $group = new CustomerGroupEntity();
        $group->setId('SWAG-CUSTOMER-GROUP-ID-3');

        $context = $this->createMock(SalesChannelContext::class);

        $context
            ->method('getCurrentCustomerGroup')
            ->willReturn($group);

        static::assertTrue(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testNotMatch(): void
    {
        $rule = (new CustomerGroupRule())->assign(['customerGroupIds' => ['SWAG-CUSTOMER-GROUP-ID-2', 'SWAG-CUSTOMER-GROUP-ID-3', 'SWAG-CUSTOMER-GROUP-ID-1']]);

        $cart = new Cart('test');

        $group = new CustomerGroupEntity();
        $group->setId('SWAG-CUSTOMER-GROUP-ID-5');

        $context = $this->createMock(SalesChannelContext::class);

        $context
            ->method('getCurrentCustomerGroup')
            ->willReturn($group);

        static::assertFalse(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }
}
