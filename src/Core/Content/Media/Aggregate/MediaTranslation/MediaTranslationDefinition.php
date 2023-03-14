<?php declare(strict_types=1);

namespace Laser\Core\Content\Media\Aggregate\MediaTranslation;

use Laser\Core\Content\Media\MediaDefinition;
use Laser\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Laser\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Laser\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Laser\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Laser\Core\Framework\DataAbstractionLayer\Field\StringField;
use Laser\Core\Framework\DataAbstractionLayer\FieldCollection;
use Laser\Core\Framework\Log\Package;

#[Package('content')]
class MediaTranslationDefinition extends EntityTranslationDefinition
{
    final public const ENTITY_NAME = 'media_translation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return MediaTranslationCollection::class;
    }

    public function getEntityClass(): string
    {
        return MediaTranslationEntity::class;
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    protected function getParentDefinitionClass(): string
    {
        return MediaDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new StringField('title', 'title'))->addFlags(new ApiAware()),
            (new LongTextField('alt', 'alt'))->addFlags(new ApiAware()),
            (new CustomFields())->addFlags(new ApiAware()),
        ]);
    }
}
