<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Cart\Price;

use Laser\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Laser\Core\Checkout\Cart\Price\Struct\PriceCollection;
use Laser\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Laser\Core\Checkout\Cart\Tax\PercentageTaxRuleBuilder;
use Laser\Core\Framework\Log\Package;
use Laser\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
class AbsolutePriceCalculator
{
    /**
     * @internal
     */
    public function __construct(
        private readonly QuantityPriceCalculator $priceCalculator,
        private readonly PercentageTaxRuleBuilder $percentageTaxRuleBuilder
    ) {
    }

    public function calculate(float $price, PriceCollection $prices, SalesChannelContext $context, int $quantity = 1): CalculatedPrice
    {
        $taxRules = $this->percentageTaxRuleBuilder->buildRules($prices->sum());

        $definition = new QuantityPriceDefinition($price, $taxRules, $quantity);

        return $this->priceCalculator->calculate($definition, $context);
    }
}