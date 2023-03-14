<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Customer\Aggregate\CustomerRecovery;

use Laser\Core\Checkout\Customer\CustomerDefinition;
use Laser\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Laser\Core\Framework\DataAbstractionLayer\Field\FkField;
use Laser\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Laser\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Laser\Core\Framework\DataAbstractionLayer\Field\IdField;
use Laser\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Laser\Core\Framework\DataAbstractionLayer\Field\StringField;
use Laser\Core\Framework\DataAbstractionLayer\FieldCollection;
use Laser\Core\Framework\Log\Package;

#[Package('customer-order')]
class CustomerRecoveryDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'customer_recovery';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return CustomerRecoveryEntity::class;
    }

    public function getCollectionClass(): string
    {
        return CustomerRecoveryCollection::class;
    }

    public function since(): ?string
    {
        return '6.1.0.0';
    }

    protected function getParentDefinitionClass(): ?string
    {
        return CustomerDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new StringField('hash', 'hash'))->addFlags(new Required()),
            (new FkField('customer_id', 'customerId', CustomerDefinition::class))->addFlags(new Required()),
            new OneToOneAssociationField('customer', 'customer_id', 'id', CustomerDefinition::class, false),
        ]);
    }
}
