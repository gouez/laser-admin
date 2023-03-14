<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Cart\Rule;

use Laser\Core\Checkout\Cart\LineItem\LineItem;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Rule\Rule;
use Laser\Core\Framework\Rule\RuleComparison;
use Laser\Core\Framework\Rule\RuleConstraints;
use Laser\Core\Framework\Rule\RuleScope;

#[Package('business-ops')]
class LineItemRule extends Rule
{
    final public const RULE_NAME = 'cartLineItem';

    /**
     * @param list<string>|null $identifiers
     *
     * @internal
     */
    public function __construct(
        protected string $operator = self::OPERATOR_EQ,
        protected ?array $identifiers = null
    ) {
        parent::__construct();
    }

    public function match(RuleScope $scope): bool
    {
        if ($scope instanceof LineItemScope) {
            return $this->lineItemMatches($scope->getLineItem());
        }

        if (!$scope instanceof CartRuleScope) {
            return false;
        }

        foreach ($scope->getCart()->getLineItems()->filterGoodsFlat() as $lineItem) {
            if ($this->lineItemMatches($lineItem)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>|null
     */
    public function getIdentifiers(): ?array
    {
        return $this->identifiers;
    }

    public function getConstraints(): array
    {
        return [
            'identifiers' => RuleConstraints::uuids(),
            'operator' => RuleConstraints::uuidOperators(false),
        ];
    }

    private function lineItemMatches(LineItem $lineItem): bool
    {
        $parentId = $lineItem->getPayloadValue('parentId');
        if ($parentId !== null && RuleComparison::uuids([$parentId], $this->identifiers, $this->operator)) {
            return true;
        }

        $referencedId = $lineItem->getReferencedId();

        return RuleComparison::uuids([$referencedId], $this->identifiers, $this->operator);
    }
}
