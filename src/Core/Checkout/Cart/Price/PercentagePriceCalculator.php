<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Cart\Price;

use Laser\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Laser\Core\Checkout\Cart\Price\Struct\CartPrice;
use Laser\Core\Checkout\Cart\Price\Struct\PriceCollection;
use Laser\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Laser\Core\Checkout\Cart\Tax\PercentageTaxRuleBuilder;
use Laser\Core\Framework\Log\Package;
use Laser\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
class PercentagePriceCalculator
{
    /**
     * @internal
     */
    public function __construct(
        private readonly CashRounding $rounding,
        private readonly QuantityPriceCalculator $priceCalculator,
        private readonly PercentageTaxRuleBuilder $percentageTaxRuleBuilder
    ) {
    }

    /**
     * Provide a negative percentage value for discount or a positive percentage value for a surcharge
     *
     * @param float $percentage 10.00 for 10%, -10.0 for -10%
     */
    public function calculate(float $percentage, PriceCollection $prices, SalesChannelContext $context): CalculatedPrice
    {
        $price = $prices->sum();

        $discount = $this->round(
            $price->getTotalPrice() / 100 * $percentage,
            $context
        );

        $rules = $this->percentageTaxRuleBuilder->buildRules($price);

        $definition = new QuantityPriceDefinition($discount, $rules, 1);

        return $this->priceCalculator->calculate($definition, $context);
    }

    private function round(float $price, SalesChannelContext $context): float
    {
        if ($context->getTaxState() !== CartPrice::TAX_STATE_GROSS && !$context->getItemRounding()->roundForNet()) {
            return $this->rounding->mathRound($price, $context->getItemRounding());
        }

        return $this->rounding->cashRound($price, $context->getItemRounding());
    }
}