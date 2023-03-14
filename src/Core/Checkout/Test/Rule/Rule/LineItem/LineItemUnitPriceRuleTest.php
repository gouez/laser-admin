<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Test\Rule\Rule\LineItem;

use PHPUnit\Framework\TestCase;
use Laser\Core\Checkout\Cart\Cart;
use Laser\Core\Checkout\Cart\LineItem\LineItem;
use Laser\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Laser\Core\Checkout\Cart\Rule\CartRuleScope;
use Laser\Core\Checkout\Cart\Rule\LineItemScope;
use Laser\Core\Checkout\Cart\Rule\LineItemUnitPriceRule;
use Laser\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Laser\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Rule\Rule;
use Laser\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 */
#[Package('business-ops')]
class LineItemUnitPriceRuleTest extends TestCase
{
    private LineItem $lineItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lineItem = (new LineItem('A', 'product'))
            ->setPrice(
                new CalculatedPrice(100, 200, new CalculatedTaxCollection(), new TaxRuleCollection())
            );
    }

    public function testRuleWithExactAmountMatch(): void
    {
        $rule = (new LineItemUnitPriceRule())->assign(['amount' => 100, 'operator' => Rule::OPERATOR_EQ]);

        $context = $this->createMock(SalesChannelContext::class);

        static::assertTrue(
            $rule->match(new LineItemScope($this->lineItem, $context))
        );

        $cart = new Cart('test');
        $cart->add($this->lineItem);
        static::assertTrue(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testRuleWithExactAmountNotMatch(): void
    {
        $rule = (new LineItemUnitPriceRule())->assign(['amount' => 99, 'operator' => Rule::OPERATOR_EQ]);

        $context = $this->createMock(SalesChannelContext::class);

        static::assertFalse(
            $rule->match(new LineItemScope($this->lineItem, $context))
        );

        $cart = new Cart('test');
        $cart->add($this->lineItem);
        static::assertFalse(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testRuleWithLowerThanEqualExactAmountMatch(): void
    {
        $rule = (new LineItemUnitPriceRule())->assign(['amount' => 100, 'operator' => Rule::OPERATOR_LTE]);

        $context = $this->createMock(SalesChannelContext::class);

        static::assertTrue(
            $rule->match(new LineItemScope($this->lineItem, $context))
        );

        $cart = new Cart('test');
        $cart->add($this->lineItem);
        static::assertTrue(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testRuleWithLowerThanEqualAmountMatch(): void
    {
        $rule = (new LineItemUnitPriceRule())->assign(['amount' => 101, 'operator' => Rule::OPERATOR_LTE]);

        $context = $this->createMock(SalesChannelContext::class);

        static::assertTrue(
            $rule->match(new LineItemScope($this->lineItem, $context))
        );

        $cart = new Cart('test');
        $cart->add($this->lineItem);
        static::assertTrue(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testRuleWithLowerThanEqualAmountNotMatch(): void
    {
        $rule = (new LineItemUnitPriceRule())->assign(['amount' => 99, 'operator' => Rule::OPERATOR_LTE]);

        $context = $this->createMock(SalesChannelContext::class);

        static::assertFalse(
            $rule->match(new LineItemScope($this->lineItem, $context))
        );

        $cart = new Cart('test');
        $cart->add($this->lineItem);
        static::assertFalse(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testRuleWithGreaterThanEqualExactAmountMatch(): void
    {
        $rule = (new LineItemUnitPriceRule())->assign(['amount' => 100, 'operator' => Rule::OPERATOR_GTE]);

        $context = $this->createMock(SalesChannelContext::class);

        static::assertTrue(
            $rule->match(new LineItemScope($this->lineItem, $context))
        );

        $cart = new Cart('test');
        $cart->add($this->lineItem);
        static::assertTrue(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testRuleWithGreaterThanEqualMatch(): void
    {
        $rule = (new LineItemUnitPriceRule())->assign(['amount' => 99, 'operator' => Rule::OPERATOR_GTE]);

        $context = $this->createMock(SalesChannelContext::class);

        static::assertTrue(
            $rule->match(new LineItemScope($this->lineItem, $context))
        );

        $cart = new Cart('test');
        $cart->add($this->lineItem);
        static::assertTrue(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testRuleWithGreaterThanEqualNotMatch(): void
    {
        $rule = (new LineItemUnitPriceRule())->assign(['amount' => 101, 'operator' => Rule::OPERATOR_GTE]);

        $context = $this->createMock(SalesChannelContext::class);

        static::assertFalse(
            $rule->match(new LineItemScope($this->lineItem, $context))
        );

        $cart = new Cart('test');
        $cart->add($this->lineItem);
        static::assertFalse(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testRuleWithNotEqualMatch(): void
    {
        $rule = (new LineItemUnitPriceRule())->assign(['amount' => 101, 'operator' => Rule::OPERATOR_NEQ]);

        $context = $this->createMock(SalesChannelContext::class);

        static::assertTrue(
            $rule->match(new LineItemScope($this->lineItem, $context))
        );

        $cart = new Cart('test');
        $cart->add($this->lineItem);
        static::assertTrue(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testRuleWithNotEqualNotMatch(): void
    {
        $rule = (new LineItemUnitPriceRule())->assign(['amount' => 100, 'operator' => Rule::OPERATOR_NEQ]);

        $context = $this->createMock(SalesChannelContext::class);

        static::assertFalse(
            $rule->match(new LineItemScope($this->lineItem, $context))
        );

        $cart = new Cart('test');
        $cart->add($this->lineItem);
        static::assertFalse(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }
}
