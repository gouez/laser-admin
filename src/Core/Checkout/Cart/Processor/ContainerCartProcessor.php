<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Cart\Processor;

use Laser\Core\Checkout\Cart\Cart;
use Laser\Core\Checkout\Cart\CartBehavior;
use Laser\Core\Checkout\Cart\CartException;
use Laser\Core\Checkout\Cart\CartProcessorInterface;
use Laser\Core\Checkout\Cart\LineItem\CartDataCollection;
use Laser\Core\Checkout\Cart\LineItem\LineItem;
use Laser\Core\Checkout\Cart\LineItem\LineItemCollection;
use Laser\Core\Checkout\Cart\Price\CurrencyPriceCalculator;
use Laser\Core\Checkout\Cart\Price\PercentagePriceCalculator;
use Laser\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Laser\Core\Checkout\Cart\Price\Struct\AbsolutePriceDefinition;
use Laser\Core\Checkout\Cart\Price\Struct\CurrencyPriceDefinition;
use Laser\Core\Checkout\Cart\Price\Struct\PercentagePriceDefinition;
use Laser\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Util\FloatComparator;
use Laser\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
class ContainerCartProcessor implements CartProcessorInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly PercentagePriceCalculator $percentageCalculator,
        private readonly QuantityPriceCalculator $quantityCalculator,
        private readonly CurrencyPriceCalculator $currencyCalculator
    ) {
    }

    public function process(CartDataCollection $data, Cart $original, Cart $toCalculate, SalesChannelContext $context, CartBehavior $behavior): void
    {
        $items = $original->getLineItems()->filterFlatByType(LineItem::CONTAINER_LINE_ITEM);
        foreach ($items as $item) {
            if ($item->getChildren()->count() <= 0) {
                $original->remove($item->getId());
            }
        }

        $items = $original->getLineItems()->filterType(LineItem::CONTAINER_LINE_ITEM);
        foreach ($items as $item) {
            $this->calculate($item, $context, $toCalculate->getLineItems());
            $toCalculate->add($item);
        }
    }

    private function calculateCollection(LineItemCollection $items, SalesChannelContext $context, \Closure $condition): void
    {
        foreach ($items as $item) {
            $match = $condition($item);

            if (!$match) {
                continue;
            }

            $this->calculate($item, $context, $items);
        }
    }

    private function calculate(LineItem $item, SalesChannelContext $context, LineItemCollection $scope): void
    {
        if ($item->getChildren()->count() > 0) {
            // we need to calculate the children in a specific order.
            // we can only calculate "referring" price (discount, surcharges) after calculating items with fix prices (products, etc)
            $this->calculateCollection($item->getChildren(), $context, fn (LineItem $item) => $item->getChildren()->count() > 0);

            $this->calculateCollection($item->getChildren(), $context, fn (LineItem $item) => $item->getPriceDefinition() instanceof QuantityPriceDefinition);

            $this->calculateCollection($item->getChildren(), $context, fn (LineItem $item) => $item->getPriceDefinition() instanceof CurrencyPriceDefinition);

            $this->calculateCollection($item->getChildren(), $context, fn (LineItem $item) => $item->getPriceDefinition() instanceof PercentagePriceDefinition);

            if (!$this->validate($item)) {
                $scope->remove($item->getId());

                return;
            }

            $item->setPrice(
                $item->getChildren()->getPrices()->sum()
            );

            return;
        }

        $definition = $item->getPriceDefinition();

        if ($definition instanceof PercentagePriceDefinition) {
            $price = $this->percentageCalculator->calculate($definition->getPercentage(), $scope->filterGoods()->getPrices(), $context);
        } elseif ($definition instanceof CurrencyPriceDefinition) {
            $price = $this->currencyCalculator->calculate($definition->getPrice(), $scope->filterGoods()->getPrices(), $context);
        } elseif ($definition instanceof QuantityPriceDefinition) {
            $price = $this->quantityCalculator->calculate($definition, $context);
        } else {
            throw CartException::missingLineItemPrice($item->getId());
        }

        $item->setPrice($price);
    }

    private function validate(LineItem $item): bool
    {
        foreach ($item->getChildren() as $child) {
            if ($child->getPrice() === null) {
                return false;
            }

            // absolute price definition are not supported here, use CurrencyPriceDefinition instead
            if ($child->getPriceDefinition() instanceof AbsolutePriceDefinition) {
                return false;
            }
        }

        $total = $item->getChildren()->getPrices()->sum()->getTotalPrice();

        if (FloatComparator::lessThan($total, 0)) {
            return false;
        }

        return true;
    }
}
