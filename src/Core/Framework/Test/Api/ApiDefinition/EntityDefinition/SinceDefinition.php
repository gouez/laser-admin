<?php declare(strict_types=1);

namespace Laser\Core\Framework\Test\Api\ApiDefinition\EntityDefinition;

use Laser\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Laser\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Laser\Core\Framework\DataAbstractionLayer\Field\IdField;
use Laser\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * @internal
 */
class SinceDefinition extends EntityDefinition
{
    public function since(): string
    {
        return '6.3.9.9';
    }

    public function getEntityName(): string
    {
        return 'since';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new ApiAware()),
        ]);
    }
}
