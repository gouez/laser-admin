<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Cart\Rule;

use Laser\Core\Checkout\Cart\LineItem\LineItem;
use Laser\Core\Checkout\Cart\LineItem\LineItemCollection;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Rule\Container\FilterRule;
use Laser\Core\Framework\Rule\Exception\UnsupportedOperatorException;
use Laser\Core\Framework\Rule\RuleComparison;
use Laser\Core\Framework\Rule\RuleConstraints;
use Laser\Core\Framework\Rule\RuleScope;

#[Package('business-ops')]
class LineItemGoodsTotalRule extends FilterRule
{
    final public const RULE_NAME = 'cartLineItemGoodsTotal';

    protected int $count;

    /**
     * @internal
     */
    public function __construct(
        protected string $operator = self::OPERATOR_EQ,
        ?int $count = null
    ) {
        parent::__construct();
        $this->count = (int) $count;
    }

    /**
     * @throws UnsupportedOperatorException
     */
    public function match(RuleScope $scope): bool
    {
        if (!$scope instanceof CartRuleScope) {
            return false;
        }

        $goods = new LineItemCollection($scope->getCart()->getLineItems()->filterGoodsFlat());
        $filter = $this->filter;
        if ($filter !== null) {
            $context = $scope->getSalesChannelContext();

            $goods = $goods->filter(static function (LineItem $lineItem) use ($filter, $context) {
                $scope = new LineItemScope($lineItem, $context);

                return $filter->match($scope);
            });
        }

        return RuleComparison::numeric($goods->getTotalQuantity(), $this->count, $this->operator);
    }

    public function getConstraints(): array
    {
        return [
            'count' => RuleConstraints::int(),
            'operator' => RuleConstraints::numericOperators(false),
        ];
    }
}