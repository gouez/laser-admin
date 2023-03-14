<?php declare(strict_types=1);

namespace Laser\Core\Content\Product\Aggregate\ProductTranslation;

use Laser\Core\Content\Product\ProductDefinition;
use Laser\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Laser\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Laser\Core\Framework\DataAbstractionLayer\Field\Flag\AllowHtml;
use Laser\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Laser\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Laser\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Laser\Core\Framework\DataAbstractionLayer\Field\ListField;
use Laser\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Laser\Core\Framework\DataAbstractionLayer\Field\StringField;
use Laser\Core\Framework\DataAbstractionLayer\FieldCollection;
use Laser\Core\Framework\Log\Package;

#[Package('inventory')]
class ProductTranslationDefinition extends EntityTranslationDefinition
{
    final public const ENTITY_NAME = 'product_translation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function isVersionAware(): bool
    {
        return true;
    }

    public function getCollectionClass(): string
    {
        return ProductTranslationCollection::class;
    }

    public function getEntityClass(): string
    {
        return ProductTranslationEntity::class;
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function getParentDefinitionClass(): string
    {
        return ProductDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new StringField('meta_description', 'metaDescription'))->addFlags(new ApiAware()),
            (new StringField('name', 'name'))->addFlags(new ApiAware(), new Required()),
            (new LongTextField('keywords', 'keywords'))->addFlags(new ApiAware()),
            (new LongTextField('description', 'description'))->addFlags(new ApiAware(), new AllowHtml()),
            (new StringField('meta_title', 'metaTitle'))->addFlags(new ApiAware()),
            (new StringField('pack_unit', 'packUnit'))->addFlags(new ApiAware()),
            (new StringField('pack_unit_plural', 'packUnitPlural'))->addFlags(new ApiAware()),
            new ListField('custom_search_keywords', 'customSearchKeywords'),
            (new JsonField('slot_config', 'slotConfig'))->addFlags(new ApiAware()),
            (new CustomFields())->addFlags(new ApiAware()),
        ]);
    }
}