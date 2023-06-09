<?php declare(strict_types=1);

namespace Laser\Core\Framework\Demodata\Generator;

use Faker\Generator;
use Laser\Core\Checkout\Cart\Rule\GoodsPriceRule;
use Laser\Core\Checkout\Customer\Rule\CustomerGroupRule;
use Laser\Core\Checkout\Customer\Rule\DaysSinceFirstLoginRule;
use Laser\Core\Content\Rule\RuleDefinition;
use Laser\Core\Defaults;
use Laser\Core\Framework\DataAbstractionLayer\EntityRepository;
use Laser\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Laser\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Laser\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Laser\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Laser\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Laser\Core\Framework\Demodata\DemodataContext;
use Laser\Core\Framework\Demodata\DemodataGeneratorInterface;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Rule\Container\AndRule;
use Laser\Core\Framework\Rule\Container\Container;
use Laser\Core\Framework\Rule\Container\FilterRule;
use Laser\Core\Framework\Rule\Container\OrRule;
use Laser\Core\Framework\Rule\DateRangeRule;
use Laser\Core\Framework\Rule\Rule;
use Laser\Core\Framework\Uuid\Uuid;
use Laser\Core\System\Currency\Rule\CurrencyRule;
use Laser\Core\Test\TestDefaults;

/**
 * @internal
 */
#[Package('core')]
class RuleGenerator implements DemodataGeneratorInterface
{
    private Generator $faker;

    /**
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $ruleRepository,
        private readonly EntityWriterInterface $writer,
        private readonly EntityRepository $paymentMethodRepository,
        private readonly EntityRepository $shippingMethodRepository,
        private readonly RuleDefinition $ruleDefinition
    ) {
    }

    public function getDefinition(): string
    {
        return RuleDefinition::class;
    }

    public function generate(int $numberOfItems, DemodataContext $context, array $options = []): void
    {
        $this->faker = $context->getFaker();

        /** @var list<string> $paymentMethodIds */
        $paymentMethodIds = $this->paymentMethodRepository->searchIds(new Criteria(), $context->getContext())->getIds();
        /** @var list<string> $shippingMethodIds */
        $shippingMethodIds = $this->shippingMethodRepository->searchIds(new Criteria(), $context->getContext())->getIds();

        $criteria = (new Criteria())->addFilter(
            new NotFilter(
                NotFilter::CONNECTION_AND,
                [
                    new EqualsAnyFilter('rule.shippingMethods.id', $shippingMethodIds),
                    new EqualsAnyFilter('rule.paymentMethods.id', $paymentMethodIds),
                ]
            )
        );

        $ids = $this->ruleRepository->searchIds($criteria, $context->getContext());

        if (!empty($ids->getIds())) {
            return;
        }

        $pool = [
            [
                'rule' => (new DaysSinceFirstLoginRule())->assign(['daysPassed' => 0]),
                'name' => 'New customer',
            ],
            [
                'rule' => (new DateRangeRule())->assign(['fromDate' => new \DateTime(), 'toDate' => (new \DateTime())->modify('+2 day')]),
                'name' => 'Next two days',
            ],
            [
                'rule' => (new GoodsPriceRule())->assign(['amount' => 5000, 'operator' => GoodsPriceRule::OPERATOR_GTE]),
                'name' => 'Cart >= 5000',
            ],
            [
                'rule' => (new CustomerGroupRule())->assign(['customerGroupIds' => [TestDefaults::FALLBACK_CUSTOMER_GROUP]]),
                'name' => 'Default group',
            ],
            [
                'rule' => (new CurrencyRule())->assign(['currencyIds' => [Defaults::CURRENCY]]),
                'name' => 'Default currency',
            ],
        ];

        $payload = [];
        for ($i = 0; $i < 20; ++$i) {
            $rules = \array_slice($pool, random_int(0, \count($pool) - 2), random_int(1, 2));

            $classes = array_column($rules, 'rule');
            $names = array_column($rules, 'name');

            $ruleData = [
                'id' => Uuid::randomHex(),
                'priority' => $i,
                'name' => implode(' + ', $names),
                'description' => $context->getFaker()->text(),
            ];

            $ruleData['conditions'][] = $this->buildChildRule(null, (new OrRule())->assign(['rules' => $classes]));

            $payload[] = $ruleData;
        }

        // nested condition
        $nestedRule = new OrRule();

        $nestedRuleData = [
            'id' => Uuid::randomHex(),
            'priority' => 20,
            'name' => 'nested rule',
            'description' => $context->getFaker()->text(),
        ];

        $this->buildNestedRule($nestedRule, $pool, 0, 6);

        $nestedRuleData['conditions'][] = $this->buildChildRule(null, $nestedRule);

        $payload[] = $nestedRuleData;

        $writeContext = WriteContext::createFromContext($context->getContext());

        $this->writer->insert($this->ruleDefinition, $payload, $writeContext);
    }

    /**
     * @param list<array{rule: Rule, name: string}> $pool
     */
    private function buildNestedRule(Rule $rule, array $pool, int $currentDepth, int $depth): Rule
    {
        if ($currentDepth === $depth) {
            return $rule;
        }

        $rules = $this->faker->randomElements($pool, 2);

        $classes = array_column($rules, 'rule');

        if ($currentDepth % 2 === 1) {
            $classes[] = $this->buildNestedRule(new OrRule(), $pool, $currentDepth + 1, $depth);
        } else {
            $classes[] = $this->buildNestedRule(new AndRule(), $pool, $currentDepth + 1, $depth);
        }

        $rule->assign(['rules' => $classes]);

        return $rule;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildChildRule(?string $parentId, Rule $rule): array
    {
        $data = [];
        $data['value'] = $rule->jsonSerialize();
        unset($data['value']['_class'], $data['value']['rules'], $data['value']['extensions']);

        if ($rule instanceof FilterRule) {
            unset($data['value']['filter']);
        }

        if (!$data['value']) {
            unset($data['value']);
        }
        $data['id'] = Uuid::randomHex();
        $data['parentId'] = $parentId;
        $data['type'] = $rule->getName();

        if ($rule instanceof Container) {
            $data['children'] = [];
            foreach ($rule->getRules() as $index => $childRule) {
                $child = $this->buildChildRule($data['id'], $childRule);
                $child['position'] = $index;
                $data['children'][] = $child;
            }
        }

        return $data;
    }
}
