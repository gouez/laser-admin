<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Promotion\Cart\Discount\Filter;

use Laser\Core\Checkout\Cart\LineItem\Group\LineItemQuantity;
use Laser\Core\Checkout\Cart\LineItem\Group\LineItemQuantityCollection;
use Laser\Core\Checkout\Cart\LineItem\LineItem;
use Laser\Core\Checkout\Cart\Price\Struct\PriceDefinitionInterface;
use Laser\Core\Checkout\Cart\Rule\LineItemScope;
use Laser\Core\Checkout\Promotion\Cart\Discount\DiscountLineItem;
use Laser\Core\Checkout\Promotion\Cart\Discount\DiscountPackage;
use Laser\Core\Checkout\Promotion\Cart\Discount\DiscountPackageCollection;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Plugin\Exception\DecorationPatternException;
use Laser\Core\Framework\Rule\Rule;
use Laser\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
class AdvancedPackageRules extends SetGroupScopeFilter
{
    public function getDecorated(): SetGroupScopeFilter
    {
        throw new DecorationPatternException(self::class);
    }

    public function filter(DiscountLineItem $discount, DiscountPackageCollection $packages, SalesChannelContext $context): DiscountPackageCollection
    {
        $priceDefinition = $discount->getPriceDefinition();

        $newPackages = [];

        foreach ($packages as $package) {
            $foundItems = [];

            foreach ($package->getMetaData() as $item) {
                $lineItem = $package->getCartItem($item->getLineItemId());

                if ($this->isRulesFilterValid($lineItem, $priceDefinition, $context)) {
                    $item = new LineItemQuantity(
                        $lineItem->getId(),
                        $lineItem->getQuantity()
                    );

                    $foundItems[] = $item;
                }
            }

            if (\count($foundItems) > 0) {
                $newPackages[] = new DiscountPackage(new LineItemQuantityCollection($foundItems));
            }
        }

        return new DiscountPackageCollection($newPackages);
    }

    private function isRulesFilterValid(LineItem $item, PriceDefinitionInterface $priceDefinition, SalesChannelContext $context): bool
    {
        // if the price definition doesnt allow filters,
        // then return valid for the item
        if (!method_exists($priceDefinition, 'getFilter')) {
            return true;
        }

        /** @var Rule|null $filter */
        $filter = $priceDefinition->getFilter();

        // if the definition exists, but is empty
        // this means we have no restrictions and thus its valid
        if (!$filter instanceof Rule) {
            return true;
        }

        // if our price definition has a filter rule
        // then extract it, and check if it matches
        $scope = new LineItemScope($item, $context);

        if ($filter->match($scope)) {
            return true;
        }

        return false;
    }
}
