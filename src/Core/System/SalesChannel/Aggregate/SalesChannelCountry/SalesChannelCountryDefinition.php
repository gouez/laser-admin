<?php declare(strict_types=1);

namespace Laser\Core\System\SalesChannel\Aggregate\SalesChannelCountry;

use Laser\Core\Framework\DataAbstractionLayer\Field\FkField;
use Laser\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Laser\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Laser\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Laser\Core\Framework\DataAbstractionLayer\FieldCollection;
use Laser\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;
use Laser\Core\Framework\Log\Package;
use Laser\Core\System\Country\CountryDefinition;
use Laser\Core\System\SalesChannel\SalesChannelDefinition;

#[Package('sales-channel')]
class SalesChannelCountryDefinition extends MappingEntityDefinition
{
    final public const ENTITY_NAME = 'sales_channel_country';

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
            (new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            (new FkField('country_id', 'countryId', CountryDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            new ManyToOneAssociationField('salesChannel', 'sales_channel_id', SalesChannelDefinition::class, 'id', false),
            new ManyToOneAssociationField('country', 'country_id', CountryDefinition::class, 'id', false),
        ]);
    }
}
