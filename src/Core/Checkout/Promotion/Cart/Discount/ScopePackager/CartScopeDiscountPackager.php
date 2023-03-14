<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Promotion\Cart\Discount\ScopePackager;

use Laser\Core\Checkout\Cart\Cart;
use Laser\Core\Checkout\Cart\LineItem\Group\LineItemQuantity;
use Laser\Core\Checkout\Cart\LineItem\Group\LineItemQuantityCollection;
use Laser\Core\Checkout\Cart\LineItem\LineItem;
use Laser\Core\Checkout\Cart\LineItem\LineItemCollection;
use Laser\Core\Checkout\Promotion\Cart\Discount\DiscountLineItem;
use Laser\Core\Checkout\Promotion\Cart\Discount\DiscountPackage;
use Laser\Core\Checkout\Promotion\Cart\Discount\DiscountPackageCollection;
use Laser\Core\Checkout\Promotion\Cart\Discount\DiscountPackager;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Plugin\Exception\DecorationPatternException;
use Laser\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
class CartScopeDiscountPackager extends DiscountPackager
{
    public function getDecorated(): DiscountPackager
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * Gets all product line items of the entire cart that
     * match the rules and conditions of the provided discount item.
     */
    public function getMatchingItems(DiscountLineItem $discount, Cart $cart, SalesChannelContext $context): DiscountPackageCollection
    {
        $allItems = $cart->getLineItems()->filter(fn (LineItem $lineItem) => $lineItem->getType() === LineItem::PRODUCT_LINE_ITEM_TYPE && $lineItem->isStackable());

        $discountPackage = $this->getDiscountPackage($allItems);
        if ($discountPackage === null) {
            return new DiscountPackageCollection([]);
        }

        return new DiscountPackageCollection([$discountPackage]);
    }

    private function getDiscountPackage(LineItemCollection $cartItems): ?DiscountPackage
    {
        $discountItems = [];
        foreach ($cartItems as $cartLineItem) {
            for ($i = 1; $i <= $cartLineItem->getQuantity(); ++$i) {
                $item = new LineItemQuantity(
                    $cartLineItem->getId(),
                    1
                );

                $discountItems[] = $item;
            }
        }

        if (\count($discountItems) === 0) {
            return null;
        }

        return new DiscountPackage(
            new LineItemQuantityCollection($discountItems)
        );
    }
}