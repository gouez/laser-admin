<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Cart\Rule;

use Laser\Core\Checkout\Cart\CartException;
use Laser\Core\Checkout\Cart\LineItem\Group\Exception\LineItemGroupPackagerNotFoundException;
use Laser\Core\Checkout\Cart\LineItem\Group\Exception\LineItemGroupSorterNotFoundException;
use Laser\Core\Checkout\Cart\LineItem\Group\LineItemGroupBuilder;
use Laser\Core\Checkout\Cart\LineItem\Group\LineItemGroupDefinition;
use Laser\Core\Content\Rule\RuleCollection;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Rule\Container\FilterRule;
use Laser\Core\Framework\Rule\RuleConstraints;
use Laser\Core\Framework\Rule\RuleScope;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

#[Package('business-ops')]
class LineItemGroupRule extends FilterRule
{
    final public const RULE_NAME = 'cartLineItemInGroup';

    protected string $groupId;

    protected string $packagerKey;

    protected float $value;

    protected string $sorterKey;

    protected ?RuleCollection $rules = null;

    /**
     * @throws CartException
     * @throws LineItemGroupPackagerNotFoundException
     * @throws LineItemGroupSorterNotFoundException
     */
    public function match(RuleScope $scope): bool
    {
        if (!$scope instanceof CartRuleScope) {
            return false;
        }

        $groupDefinition = new LineItemGroupDefinition(
            $this->groupId,
            $this->packagerKey,
            $this->value,
            $this->sorterKey,
            $this->rules ?? new RuleCollection()
        );

        /** @var LineItemGroupBuilder|null $builder */
        $builder = $scope->getCart()->getData()->get(LineItemGroupBuilder::class);

        if (!$builder instanceof LineItemGroupBuilder) {
            return false;
        }

        $results = $builder->findGroupPackages(
            [$groupDefinition],
            $scope->getCart(),
            $scope->getSalesChannelContext()
        );

        return $results->hasFoundItems();
    }

    public function getConstraints(): array
    {
        return [
            'groupId' => RuleConstraints::string(),
            'packagerKey' => RuleConstraints::string(),
            'value' => RuleConstraints::float(),
            'sorterKey' => RuleConstraints::string(),
            'rules' => [new NotBlank(), new Type('container')],
        ];
    }
}
