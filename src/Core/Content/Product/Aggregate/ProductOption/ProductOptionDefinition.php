<?php declare(strict_types=1);

namespace Laser\Core\Content\Product\Aggregate\ProductOption;

use Laser\Core\Content\Product\ProductDefinition;
use Laser\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionDefinition;
use Laser\Core\Framework\DataAbstractionLayer\Field\FkField;
use Laser\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Laser\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Laser\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Laser\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Laser\Core\Framework\DataAbstractionLayer\FieldCollection;
use Laser\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;
use Laser\Core\Framework\Log\Package;

#[Package('inventory')]
class ProductOptionDefinition extends MappingEntityDefinition
{
    final public const ENTITY_NAME = 'product_option';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new FkField('product_id', 'productId', ProductDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            (new ReferenceVersionField(ProductDefinition::class))->addFlags(new Required()),
            (new FkField('property_group_option_id', 'optionId', PropertyGroupOptionDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            new ManyToOneAssociationField('product', 'product_id', ProductDefinition::class, 'id'),
            new ManyToOneAssociationField('option', 'property_group_option_id', PropertyGroupOptionDefinition::class, 'id'),
        ]);
    }
}
