<?php declare(strict_types=1);

namespace Cart\LineItem\Group\RuleMatcher;

use PHPUnit\Framework\TestCase;
use Laser\Core\Checkout\Cart\Cart;
use Laser\Core\Checkout\Cart\LineItem\Group\AbstractProductLineItemProvider;
use Laser\Core\Checkout\Cart\LineItem\Group\ProductLineItemProvider;
use Laser\Core\Checkout\Cart\LineItem\LineItem;
use Laser\Core\Checkout\Cart\LineItem\LineItemCollection;
use Laser\Core\Checkout\Test\Cart\LineItem\Group\Helpers\Traits\LineItemTestFixtureBehaviour;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Plugin\Exception\DecorationPatternException;
use Laser\Core\Framework\Uuid\Uuid;

/**
 * @internal
 */
#[Package('checkout')]
class ProductLineItemProviderTest extends TestCase
{
    use LineItemTestFixtureBehaviour;

    private AbstractProductLineItemProvider $provider;

    public function setUp(): void
    {
        $this->provider = new ProductLineItemProvider();
    }

    public function testIsMatchingReturnProductLineItem(): void
    {
        $cart = $this->getCart();

        static::assertEquals(4, $cart->getLineItems()->count());

        $lineItems = $this->provider->getProducts($cart);

        static::assertEquals(1, $lineItems->count());
        static::assertEquals(LineItem::PRODUCT_LINE_ITEM_TYPE, $lineItems->first()->getType());
    }

    public function testItThrowsDecorationPatternException(): void
    {
        $this->expectException(DecorationPatternException::class);

        $this->provider->getDecorated();
    }

    private function getCart(): Cart
    {
        $items = [
            new LineItem(Uuid::randomHex(), LineItem::PRODUCT_LINE_ITEM_TYPE),
            new LineItem(Uuid::randomHex(), LineItem::PROMOTION_LINE_ITEM_TYPE),
            new LineItem(Uuid::randomHex(), LineItem::CREDIT_LINE_ITEM_TYPE),
            new LineItem(Uuid::randomHex(), LineItem::CUSTOM_LINE_ITEM_TYPE),
        ];

        $cart = new Cart('token');
        $cart->addLineItems(new LineItemCollection($items));

        return $cart;
    }
}