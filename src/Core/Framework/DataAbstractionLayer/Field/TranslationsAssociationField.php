<?php declare(strict_types=1);

namespace Laser\Core\Framework\DataAbstractionLayer\Field;

use Laser\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Laser\Core\Framework\DataAbstractionLayer\FieldSerializer\TranslationsAssociationFieldSerializer;
use Laser\Core\Framework\Log\Package;

#[Package('core')]
class TranslationsAssociationField extends OneToManyAssociationField
{
    final public const PRIORITY = 90;

    public function __construct(
        string $referenceClass,
        string $referenceField,
        string $propertyName = 'translations',
        string $localField = 'id'
    ) {
        parent::__construct($propertyName, $referenceClass, $referenceField, $localField);
        $this->addFlags(new CascadeDelete());
    }

    public function getLanguageField(): string
    {
        return 'language_id';
    }

    public function getExtractPriority(): int
    {
        return self::PRIORITY;
    }

    protected function getSerializerClass(): string
    {
        return TranslationsAssociationFieldSerializer::class;
    }
}
