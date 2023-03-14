<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Cart\Rule;

use Laser\Core\Checkout\Cart\CartException;
use Laser\Core\Checkout\Cart\LineItem\LineItem;
use Laser\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerDefinition;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Rule\Exception\UnsupportedOperatorException;
use Laser\Core\Framework\Rule\Rule;
use Laser\Core\Framework\Rule\RuleComparison;
use Laser\Core\Framework\Rule\RuleConfig;
use Laser\Core\Framework\Rule\RuleConstraints;
use Laser\Core\Framework\Rule\RuleScope;

#[Package('business-ops')]
class LineItemOfManufacturerRule extends Rule
{
    final public const RULE_NAME = 'cartLineItemOfManufacturer';

    /**
     * @internal
     *
     * @param list<string> $manufacturerIds
     */
    public function __construct(
        protected string $operator = self::OPERATOR_EQ,
        protected array $manufacturerIds = []
    ) {
        parent::__construct();
    }

    /**
     * @throws UnsupportedOperatorException
     * @throws CartException
     */
    public function match(RuleScope $scope): bool
    {
        if ($scope instanceof LineItemScope) {
            return $this->matchesOneOfManufacturers($scope->getLineItem());
        }

        if (!$scope instanceof CartRuleScope) {
            return false;
        }

        foreach ($scope->getCart()->getLineItems()->filterGoodsFlat() as $lineItem) {
            if ($this->matchesOneOfManufacturers($lineItem)) {
                return true;
            }
        }

        return false;
    }

    public function getConstraints(): array
    {
        $constraints = [
            'operator' => RuleConstraints::uuidOperators(),
        ];

        if ($this->operator === self::OPERATOR_EMPTY) {
            return $constraints;
        }

        $constraints['manufacturerIds'] = RuleConstraints::uuids();

        return $constraints;
    }

    public function getConfig(): RuleConfig
    {
        return (new RuleConfig())
            ->operatorSet(RuleConfig::OPERATOR_SET_STRING, true, true)
            ->entitySelectField('manufacturerIds', ProductManufacturerDefinition::ENTITY_NAME, true);
    }

    /**
     * @throws UnsupportedOperatorException
     * @throws CartException
     */
    private function matchesOneOfManufacturers(LineItem $lineItem): bool
    {
        $manufacturerId = (string) $lineItem->getPayloadValue('manufacturerId');
        $manufacturerArray = ($manufacturerId === '') ? [] : [$manufacturerId];

        if ($this->operator === self::OPERATOR_NEQ) {
            return !\in_array($manufacturerId, $this->manufacturerIds, true);
        }

        return RuleComparison::uuids($manufacturerArray, $this->manufacturerIds, $this->operator);
    }
}
