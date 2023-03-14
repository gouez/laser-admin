<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Cart\Rule;

use Laser\Core\Checkout\Cart\LineItem\LineItem;
use Laser\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Laser\Core\Checkout\Cart\Price\Struct\ListPrice;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Rule\Exception\UnsupportedOperatorException;
use Laser\Core\Framework\Rule\Rule;
use Laser\Core\Framework\Rule\RuleComparison;
use Laser\Core\Framework\Rule\RuleConfig;
use Laser\Core\Framework\Rule\RuleConstraints;
use Laser\Core\Framework\Rule\RuleScope;

#[Package('business-ops')]
class LineItemListPriceRatioRule extends Rule
{
    final public const RULE_NAME = 'cartLineItemListPriceRatio';

    /**
     * @internal
     */
    public function __construct(
        protected string $operator = self::OPERATOR_EQ,
        protected ?float $amount = null
    ) {
        parent::__construct();
    }

    public function match(RuleScope $scope): bool
    {
        if ($scope instanceof LineItemScope) {
            return $this->matchesListPriceCondition($scope->getLineItem());
        }

        if (!$scope instanceof CartRuleScope) {
            return false;
        }

        foreach ($scope->getCart()->getLineItems()->filterGoodsFlat() as $lineItem) {
            if ($this->matchesListPriceCondition($lineItem)) {
                return true;
            }
        }

        return false;
    }

    public function getConstraints(): array
    {
        $constraints = [
            'operator' => RuleConstraints::numericOperators(),
        ];

        if ($this->operator === self::OPERATOR_EMPTY) {
            return $constraints;
        }

        $constraints['amount'] = RuleConstraints::float();

        return $constraints;
    }

    public function getConfig(): RuleConfig
    {
        return (new RuleConfig())
            ->operatorSet(RuleConfig::OPERATOR_SET_NUMBER, true)
            ->numberField('amount');
    }

    /**
     * @throws UnsupportedOperatorException
     */
    private function matchesListPriceCondition(LineItem $lineItem): bool
    {
        $calculatedPrice = $lineItem->getPrice();

        if (!$calculatedPrice instanceof CalculatedPrice) {
            return RuleComparison::isNegativeOperator($this->operator);
        }

        $listPrice = $calculatedPrice->getListPrice();

        $listPriceRatioAmount = null;
        if ($listPrice instanceof ListPrice) {
            $listPriceRatioAmount = $listPrice->getPercentage();
        }

        return RuleComparison::numeric($listPriceRatioAmount, (float) $this->amount, $this->operator);
    }
}