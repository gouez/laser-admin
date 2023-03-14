<?php declare(strict_types=1);

namespace Laser\Core\Content\Product\Aggregate\ProductVisibility;

use Laser\Core\Framework\Context;
use Laser\Core\Framework\DataAbstractionLayer\Dbal\EntityHydrator;
use Laser\Core\Framework\DataAbstractionLayer\Entity;
use Laser\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Uuid\Uuid;

#[Package('inventory')]
class ProductVisibilityHydrator extends EntityHydrator
{
    protected function assign(EntityDefinition $definition, Entity $entity, string $root, array $row, Context $context): Entity
    {
        if (isset($row[$root . '.id'])) {
            $entity->id = Uuid::fromBytesToHex($row[$root . '.id']);
        }
        if (isset($row[$root . '.productId'])) {
            $entity->productId = Uuid::fromBytesToHex($row[$root . '.productId']);
        }
        if (isset($row[$root . '.salesChannelId'])) {
            $entity->salesChannelId = Uuid::fromBytesToHex($row[$root . '.salesChannelId']);
        }
        if (isset($row[$root . '.visibility'])) {
            $entity->visibility = (int) $row[$root . '.visibility'];
        }
        if (isset($row[$root . '.createdAt'])) {
            $entity->createdAt = new \DateTimeImmutable($row[$root . '.createdAt']);
        }
        if (isset($row[$root . '.updatedAt'])) {
            $entity->updatedAt = new \DateTimeImmutable($row[$root . '.updatedAt']);
        }
        $entity->salesChannel = $this->manyToOne($row, $root, $definition->getField('salesChannel'), $context);
        $entity->product = $this->manyToOne($row, $root, $definition->getField('product'), $context);

        $this->translate($definition, $entity, $row, $root, $context, $definition->getTranslatedFields());
        $this->hydrateFields($definition, $entity, $root, $row, $context, $definition->getExtensionFields());

        return $entity;
    }
}
