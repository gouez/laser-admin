<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Cart\LineItem\Group;

use Laser\Core\Checkout\Cart\Cart;
use Laser\Core\Checkout\Cart\LineItem\LineItem;
use Laser\Core\Checkout\Cart\LineItem\LineItemCollection;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Plugin\Exception\DecorationPatternException;

#[Package('checkout')]
class ProductLineItemProvider extends AbstractProductLineItemProvider
{
    public function getDecorated(): AbstractProductLineItemProvider
    {
        throw new DecorationPatternException(self::class);
    }

    public function getProducts(Cart $cart): LineItemCollection
    {
        return $cart->getLineItems()->filterType(LineItem::PRODUCT_LINE_ITEM_TYPE);
    }
}
