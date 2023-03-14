<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Customer\Rule;

use Laser\Core\Checkout\CheckoutRuleScope;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Rule\Exception\UnsupportedValueException;
use Laser\Core\Framework\Rule\Rule;
use Laser\Core\Framework\Rule\RuleComparison;
use Laser\Core\Framework\Rule\RuleConfig;
use Laser\Core\Framework\Rule\RuleConstraints;
use Laser\Core\Framework\Rule\RuleScope;

#[Package('business-ops')]
class CustomerAgeRule extends Rule
{
    final public const RULE_NAME = 'customerAge';

    /**
     * @internal
     */
    public function __construct(
        protected string $operator = self::OPERATOR_EQ,
        protected ?float $age = null
    ) {
        parent::__construct();
    }

    public function match(RuleScope $scope): bool
    {
        if (!$scope instanceof CheckoutRuleScope) {
            return false;
        }

        if (!$customer = $scope->getSalesChannelContext()->getCustomer()) {
            return RuleComparison::isNegativeOperator($this->operator);
        }

        if (!$this->age && $this->operator !== self::OPERATOR_EMPTY) {
            throw new UnsupportedValueException(\gettype($this->age), self::class);
        }

        if (!$birthday = $customer->getBirthday()) {
            return RuleComparison::isNegativeOperator($this->operator);
        }

        $birthday = (new \DateTime())->setTimestamp($birthday->getTimestamp());
        $now = new \DateTime();

        $age = $now->diff($birthday)->y;

        return RuleComparison::numeric($age, $this->age, $this->operator);
    }

    public function getConstraints(): array
    {
        $constraints = [
            'operator' => RuleConstraints::numericOperators(true),
        ];

        if ($this->operator === self::OPERATOR_EMPTY) {
            return $constraints;
        }

        $constraints['age'] = RuleConstraints::float();

        return $constraints;
    }

    public function getConfig(): RuleConfig
    {
        return (new RuleConfig())
            ->operatorSet(RuleConfig::OPERATOR_SET_NUMBER, true)
            ->intField('age', ['unit' => RuleConfig::UNIT_AGE]);
    }
}
