<?php declare(strict_types=1);

namespace Laser\Core\Framework\DataAbstractionLayer\Field;

use Laser\Core\Framework\DataAbstractionLayer\FieldSerializer\DateTimeFieldSerializer;
use Laser\Core\Framework\Log\Package;

#[Package('core')]
class DateTimeField extends Field implements StorageAware
{
    public function __construct(
        private readonly string $storageName,
        string $propertyName
    ) {
        parent::__construct($propertyName);
    }

    public function getStorageName(): string
    {
        return $this->storageName;
    }

    protected function getSerializerClass(): string
    {
        return DateTimeFieldSerializer::class;
    }
}
