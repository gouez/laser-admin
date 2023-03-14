<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Promotion\Cart;

use Laser\Core\Checkout\Cart\Cart;
use Laser\Core\Checkout\Cart\CartBehavior;
use Laser\Core\Checkout\Cart\CartException;
use Laser\Core\Checkout\Cart\LineItem\Group\Exception\LineItemGroupPackagerNotFoundException;
use Laser\Core\Checkout\Cart\LineItem\Group\Exception\LineItemGroupSorterNotFoundException;
use Laser\Core\Checkout\Cart\LineItem\Group\LineItemGroupBuilder;
use Laser\Core\Checkout\Cart\LineItem\LineItem;
use Laser\Core\Checkout\Cart\LineItem\LineItemCollection;
use Laser\Core\Checkout\Cart\LineItem\LineItemFlatCollection;
use Laser\Core\Checkout\Cart\LineItem\LineItemQuantitySplitter;
use Laser\Core\Checkout\Cart\Price\AbsolutePriceCalculator;
use Laser\Core\Checkout\Cart\Price\AmountCalculator;
use Laser\Core\Checkout\Cart\Price\PercentagePriceCalculator;
use Laser\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Laser\Core\Checkout\Cart\Price\Struct\CartPrice;
use Laser\Core\Checkout\Cart\Price\Struct\PriceCollection;
use Laser\Core\Checkout\Cart\Price\Struct\PriceDefinitionInterface;
use Laser\Core\Checkout\Cart\Rule\CartRuleScope;
use Laser\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Laser\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Laser\Core\Checkout\Promotion\Aggregate\PromotionDiscount\PromotionDiscountEntity;
use Laser\Core\Checkout\Promotion\Cart\Discount\Calculator\DiscountAbsoluteCalculator;
use Laser\Core\Checkout\Promotion\Cart\Discount\Calculator\DiscountFixedPriceCalculator;
use Laser\Core\Checkout\Promotion\Cart\Discount\Calculator\DiscountFixedUnitPriceCalculator;
use Laser\Core\Checkout\Promotion\Cart\Discount\Calculator\DiscountPercentageCalculator;
use Laser\Core\Checkout\Promotion\Cart\Discount\Composition\DiscountCompositionBuilder;
use Laser\Core\Checkout\Promotion\Cart\Discount\DiscountCalculatorResult;
use Laser\Core\Checkout\Promotion\Cart\Discount\DiscountLineItem;
use Laser\Core\Checkout\Promotion\Cart\Discount\DiscountPackageCollection;
use Laser\Core\Checkout\Promotion\Cart\Discount\DiscountPackager;
use Laser\Core\Checkout\Promotion\Cart\Discount\Filter\AdvancedPackagePicker;
use Laser\Core\Checkout\Promotion\Cart\Discount\Filter\Exception\FilterSorterNotFoundException;
use Laser\Core\Checkout\Promotion\Cart\Discount\Filter\PackageFilter;
use Laser\Core\Checkout\Promotion\Cart\Discount\Filter\SetGroupScopeFilter;
use Laser\Core\Checkout\Promotion\Cart\Error\PromotionExcludedError;
use Laser\Core\Checkout\Promotion\Exception\DiscountCalculatorNotFoundException;
use Laser\Core\Checkout\Promotion\Exception\InvalidPriceDefinitionException;
use Laser\Core\Checkout\Promotion\Exception\InvalidScopeDefinitionException;
use Laser\Core\Checkout\Promotion\Exception\SetGroupNotFoundException;
use Laser\Core\Framework\Log\Package;
use Laser\Core\System\SalesChannel\SalesChannelContext;

/**
 * Cart Promotion Calculator
 */
#[Package('checkout')]
class PromotionCalculator
{
    use PromotionCartInformationTrait;

    /**
     * @internal
     */
    public function __construct(
        private readonly AmountCalculator $amountCalculator,
        private readonly AbsolutePriceCalculator $absolutePriceCalculator,
        private readonly LineItemGroupBuilder $groupBuilder,
        private readonly DiscountCompositionBuilder $discountCompositionBuilder,
        private readonly PackageFilter $advancedFilter,
        private readonly AdvancedPackagePicker $advancedPicker,
        private readonly SetGroupScopeFilter $advancedRules,
        private readonly LineItemQuantitySplitter $lineItemQuantitySplitter,
        private readonly PercentagePriceCalculator $percentagePriceCalculator,
        private readonly DiscountPackager $cartScopeDiscountPackager,
        private readonly DiscountPackager $setGroupScopeDiscountPackager,
        private readonly DiscountPackager $setScopeDiscountPackager
    ) {
    }

    /**
     * Calculates the cart including the new discount line items.
     * The calculation process will first determine the correct values for
     * the different discount line item types (percentage, absolute, ...) and then
     * recalculate the whole cart with these new items.
     *
     * @throws DiscountCalculatorNotFoundException
     * @throws InvalidPriceDefinitionException
     * @throws CartException
     */
    public function calculate(LineItemCollection $discountLineItems, Cart $original, Cart $calculated, SalesChannelContext $context, CartBehavior $behaviour): void
    {
        // array that holds all excluded promotion ids.
        // if a promotion has exclusions they are added on the stack
        $exclusions = $this->buildExclusions($discountLineItems, $calculated, $context);

        // @todo order $discountLineItems by priority

        foreach ($discountLineItems as $discountItem) {
            // if we dont have a scope
            // then skip it, it might not belong to us
            if (!$discountItem->hasPayloadValue('discountScope')) {
                continue;
            }

            // deliveries have their own processor and calculator
            if ($discountItem->getPayloadValue('discountScope') === PromotionDiscountEntity::SCOPE_DELIVERY) {
                continue;
            }

            $isAutomaticDiscount = $this->isAutomaticDiscount($discountItem);

            // we have to verify if the line item is still valid
            // depending on the added requirements and conditions.
            if (!$this->isRequirementValid($discountItem, $calculated, $context)) {
                // hide the notEligibleErrors on automatic discounts
                if (!$isAutomaticDiscount) {
                    $this->addPromotionNotEligibleError($discountItem->getLabel() ?? $discountItem->getId(), $calculated);
                }

                continue;
            }

            // if promotion is on exclusions stack it is ignored
            if (!$discountItem->hasPayloadValue('promotionId')) {
                continue;
            }

            $promotionId = $discountItem->getPayloadValue('promotionId');

            if (\array_key_exists($promotionId, $exclusions)) {
                if (!$isAutomaticDiscount) {
                    $calculated->addErrors(new PromotionExcludedError($discountItem->getDescription() ?? $discountItem->getId()));
                }

                continue;
            }

            $result = $this->calculateDiscount($discountItem, $calculated, $context);

            // if our price is 0,00 because of whatever reason, make sure to skip it.
            // this can be if the price-definition filter is none,
            // or if a fixed price is set to the price of the product itself.
            if (abs($result->getPrice()->getTotalPrice()) === 0.0) {
                continue;
            }

            // use our calculated price
            $discountItem->setPrice($result->getPrice());

            // also add our discounted items and their meta data
            // to our discount line item payload
            $discountItem->setPayloadValue(
                'composition',
                $this->discountCompositionBuilder->buildCompositionPayload($result->getCompositionItems())
            );

            // add our discount item to the cart
            $calculated->add($discountItem);

            $this->addPromotionAddedNotice($original, $calculated, $discountItem);

            // recalculate for every new discount to get the correct
            // prices for any upcoming iterations
            $this->calculateCart($calculated, $context);
        }
    }

    /**
     * This function builds a complete list of promotions
     * that are excluded somehow.
     * The validation which one to take will be done later.
     *
     * @return array<mixed, boolean>
     */
    private function buildExclusions(LineItemCollection $discountLineItems, Cart $calculated, SalesChannelContext $context): array
    {
        // array that holds all excluded promotion ids.
        // if a promotion has exclusions they are added on the stack
        $exclusions = [];

        foreach ($discountLineItems as $discountItem) {
            // if we dont have a scope
            // then skip it, it might not belong to us
            if (!$discountItem->hasPayloadValue('discountScope')) {
                continue;
            }

            // if promotion is on exclusions stack it is ignored
            if ($discountItem->hasPayloadValue('promotionId')) {
                $promotionId = $discountItem->getPayloadValue('promotionId');

                // if promotion is on exclusions stack it is ignored
                // this avoids cycles that both promotions exclude each other
                if (isset($exclusions[$promotionId])) {
                    continue;
                }

                if ($discountItem->getPayloadValue('preventCombination')) {
                    $payloadExclusions = [];
                    foreach ($discountLineItems as $exclusionItem) {
                        if (!$exclusionItem->hasPayloadValue('promotionId')) {
                            continue;
                        }

                        $promotionIdToExclude = $exclusionItem->getPayloadValue('promotionId');
                        if ($promotionIdToExclude === $promotionId) {
                            continue;
                        }

                        $payloadExclusions[] = $promotionIdToExclude;
                    }

                    $discountItem->setPayloadValue('exclusions', $payloadExclusions);
                }
            }

            // add all exclusions to the stack
            foreach ($discountItem->getPayloadValue('exclusions') as $id) {
                // check if the promotion is active by its conditions
                if ($this->isRequirementValid($discountItem, $calculated, $context)) {
                    $exclusions[$id] = true;
                }
            }
        }

        return $exclusions;
    }

    /**
     * Calculates and returns the discount based on the settings of
     * the provided discount line item.
     *
     * @throws DiscountCalculatorNotFoundException
     * @throws FilterSorterNotFoundException
     * @throws InvalidPriceDefinitionException
     * @throws InvalidScopeDefinitionException
     * @throws CartException
     * @throws LineItemGroupPackagerNotFoundException
     * @throws LineItemGroupSorterNotFoundException
     * @throws SetGroupNotFoundException
     */
    private function calculateDiscount(LineItem $lineItem, Cart $calculatedCart, SalesChannelContext $context): DiscountCalculatorResult
    {
        /** @var string $label */
        $label = $lineItem->getLabel();

        /** @var PriceDefinitionInterface $priceDefinition */
        $priceDefinition = $lineItem->getPriceDefinition();

        $discount = new DiscountLineItem(
            $label,
            $priceDefinition,
            $lineItem->getPayload(),
            $lineItem->getReferencedId()
        );

        $packager = match ($discount->getScope()) {
            PromotionDiscountEntity::SCOPE_CART => $this->cartScopeDiscountPackager,
            PromotionDiscountEntity::SCOPE_SET => $this->setScopeDiscountPackager,
            PromotionDiscountEntity::SCOPE_SETGROUP => $this->setGroupScopeDiscountPackager,
            default => throw new InvalidScopeDefinitionException($discount->getScope()),
        };

        $packages = $packager->getMatchingItems($discount, $calculatedCart, $context);

        // check if no result is found,
        // then this would mean -> no discount
        if ($packages->count() <= 0) {
            return new DiscountCalculatorResult(
                new CalculatedPrice(0, 0, new CalculatedTaxCollection(), new TaxRuleCollection(), 1),
                []
            );
        }

        // remember our initial package count
        $originalPackageCount = $packages->count();

        $packages = $this->enrichPackagesWithCartData($packages, $calculatedCart, $context);

        // every scope packager can have an additional
        // list of rules that can be used to filter out items.
        // thus we enrich our current package with items
        // and run it through the advanced rules if existing
        if ($discount->getScope() !== PromotionDiscountEntity::SCOPE_SETGROUP) {
            $packages = $this->advancedRules->filter($discount, $packages, $context);
        }

        // depending on the selected picker of our
        // discount, the packages might be restructure
        // also make sure we have correct cart items in our restructured packages from the picker
        $packages = $this->advancedPicker->pickItems($discount, $packages);
        $packages = $this->enrichPackagesWithCartData($packages, $calculatedCart, $context);

        // if we have any graduation settings, make sure to reduce the items
        // that are eligible for our discount by executing our graduation resolver.
        $packages = $this->advancedFilter->filterPackages($discount, $packages, $originalPackageCount);
        $packages = $this->enrichPackagesWithCartData($packages, $calculatedCart, $context);

        $calculator = match ($discount->getType()) {
            PromotionDiscountEntity::TYPE_ABSOLUTE => new DiscountAbsoluteCalculator($this->absolutePriceCalculator),
            PromotionDiscountEntity::TYPE_PERCENTAGE => new DiscountPercentageCalculator($this->absolutePriceCalculator, $this->percentagePriceCalculator),
            PromotionDiscountEntity::TYPE_FIXED => new DiscountFixedPriceCalculator($this->absolutePriceCalculator),
            PromotionDiscountEntity::TYPE_FIXED_UNIT => new DiscountFixedUnitPriceCalculator($this->absolutePriceCalculator),
            default => throw new DiscountCalculatorNotFoundException($discount->getType()),
        };

        $result = $calculator->calculate($discount, $packages, $context);

        // now aggregate any composition items
        // which might be duplicated due to separate packages
        $aggregatedCompositionItems = $this->discountCompositionBuilder->aggregateCompositionItems($result->getCompositionItems());
        $result = new DiscountCalculatorResult($result->getPrice(), $aggregatedCompositionItems);

        // get the cart total price => discount may never be higher than this value
        $maxDiscountValue = $this->getMaxDiscountValue($calculatedCart, $context);

        // if our price is larger than the max discount value,
        // then use the max discount value as negative discount
        if (abs($result->getPrice()->getTotalPrice()) > abs($maxDiscountValue)) {
            $result = $this->limitDiscountResult($maxDiscountValue, $packages->getAffectedPrices(), $result, $context);
        }

        return $result;
    }

    /**
     * Calculates a max discount value based on current cart and customer group.
     * If customer is in net customer group, get the cart's net value,
     * otherwise use the gross value as maximum value.
     */
    private function getMaxDiscountValue(Cart $cart, SalesChannelContext $context): float
    {
        if ($context->getTaxState() === CartPrice::TAX_STATE_NET) {
            return $cart->getPrice()->getNetPrice();
        }

        return $cart->getPrice()->getTotalPrice();
    }

    /**
     * This function can be used to limit the provided discount data
     * to a maximum threshold value.
     * It will recalculate the price and adjust all discount composition items
     * to match the demanded total price.
     */
    private function limitDiscountResult(float $maxDiscountValue, PriceCollection $priceCollection, DiscountCalculatorResult $originalResult, SalesChannelContext $context): DiscountCalculatorResult
    {
        $price = $this->absolutePriceCalculator->calculate(
            -abs($maxDiscountValue),
            $priceCollection,
            $context
        );

        $adjustedItems = $this->discountCompositionBuilder->adjustCompositionItemValues($price, $originalResult->getCompositionItems());

        // update our result price to the new one
        return new DiscountCalculatorResult($price, $adjustedItems);
    }

    /**
     * Validates the included requirements and returns if the
     * line item is allowed to be added to the actual cart.
     */
    private function isRequirementValid(LineItem $lineItem, Cart $calculated, SalesChannelContext $context): bool
    {
        // if we dont have any requirement, then it's obviously valid
        if (!$lineItem->getRequirement()) {
            return true;
        }

        $scopeWithoutLineItem = new CartRuleScope($calculated, $context);

        // set our currently registered group builder in our cart data
        // to be able to use that one within our line item rule
        $data = $scopeWithoutLineItem->getCart()->getData();
        $data->set(LineItemGroupBuilder::class, $this->groupBuilder);

        return $lineItem->getRequirement()->match($scopeWithoutLineItem);
    }

    /**
     * calculate the cart sum
     */
    private function calculateCart(Cart $cart, SalesChannelContext $context): void
    {
        $amount = $this->amountCalculator->calculate(
            $cart->getLineItems()->getPrices(),
            $cart->getDeliveries()->getShippingCosts(),
            $context
        );

        $cart->setPrice($amount);
    }

    /**
     * @throws CartException
     */
    private function enrichPackagesWithCartData(DiscountPackageCollection $result, Cart $cart, SalesChannelContext $context): DiscountPackageCollection
    {
        // set the line item from the cart for each unit
        foreach ($result as $package) {
            $cartItemsForUnit = new LineItemFlatCollection();

            foreach ($package->getMetaData() as $item) {
                /** @var LineItem $cartItem */
                $cartItem = $cart->get($item->getLineItemId());

                $cartItem->setStackable(true);

                // create a new item with only a quantity of x
                // including calculated price for our original cart item
                $qtyItem = $this->lineItemQuantitySplitter->split($cartItem, $item->getQuantity(), $context);

                // add the single item to our unit
                $cartItemsForUnit->add($qtyItem);
            }

            $package->setCartItems($cartItemsForUnit);
        }

        return $result;
    }

    private function isAutomaticDiscount(LineItem $discountItem): bool
    {
        return empty($discountItem->getPayloadValue('code'));
    }
}