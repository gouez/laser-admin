<?php declare(strict_types=1);

namespace Laser\Core\Framework\Test\DataAbstractionLayer\Search\Definition;

use Laser\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Laser\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Laser\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Laser\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Laser\Core\Framework\DataAbstractionLayer\Field\IdField;
use Laser\Core\Framework\DataAbstractionLayer\Field\IntField;
use Laser\Core\Framework\DataAbstractionLayer\Field\StringField;
use Laser\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * @internal
 */
class GroupByTestDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'group_by_test';
    }

    public function since(): string
    {
        return '6.1.0';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new ApiAware(), new PrimaryKey(), new Required()),
            (new StringField('name', 'name'))->addFlags(new ApiAware(), new Required()),
            (new IntField('field1', 'field1'))->addFlags(new ApiAware(), new Required()),
            (new IntField('field2', 'field2'))->addFlags(new ApiAware(), new Required()),
        ]);
    }
}
