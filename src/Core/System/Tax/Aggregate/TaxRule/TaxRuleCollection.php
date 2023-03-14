<?php declare(strict_types=1);

namespace Laser\Core\System\Tax\Aggregate\TaxRule;

use Laser\Core\Framework\DataAbstractionLayer\EntityCollection;
use Laser\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<TaxRuleEntity>
 */
#[Package('customer-order')]
class TaxRuleCollection extends EntityCollection
{
    public function sortByTypePosition(): void
    {
        $this->sort(fn (TaxRuleEntity $entityA, TaxRuleEntity $entityB) => $entityA->getType()->getPosition() <=> $entityB->getType()->getPosition());
    }

    public function getApiAlias(): string
    {
        return 'tax_rule_collection';
    }

    protected function getExpectedClass(): string
    {
        return TaxRuleEntity::class;
    }
}
