<?php declare(strict_types=1);

namespace Laser\Core\Content\Product\Aggregate\ProductCrossSellingAssignedProducts;

use Laser\Core\Framework\Context;
use Laser\Core\Framework\DataAbstractionLayer\Dbal\EntityHydrator;
use Laser\Core\Framework\DataAbstractionLayer\Entity;
use Laser\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Uuid\Uuid;

#[Package('inventory')]
class ProductCrossSellingAssignedProductsHydrator extends EntityHydrator
{
    protected function assign(EntityDefinition $definition, Entity $entity, string $root, array $row, Context $context): Entity
    {
        if (isset($row[$root . '.id'])) {
            $entity->id = Uuid::fromBytesToHex($row[$root . '.id']);
        }
        if (isset($row[$root . '.crossSellingId'])) {
            $entity->crossSellingId = Uuid::fromBytesToHex($row[$root . '.crossSellingId']);
        }
        if (isset($row[$root . '.productId'])) {
            $entity->productId = Uuid::fromBytesToHex($row[$root . '.productId']);
        }
        if (isset($row[$root . '.position'])) {
            $entity->position = (int) $row[$root . '.position'];
        }
        if (isset($row[$root . '.createdAt'])) {
            $entity->createdAt = new \DateTimeImmutable($row[$root . '.createdAt']);
        }
        if (isset($row[$root . '.updatedAt'])) {
            $entity->updatedAt = new \DateTimeImmutable($row[$root . '.updatedAt']);
        }
        $entity->product = $this->manyToOne($row, $root, $definition->getField('product'), $context);
        $entity->crossSelling = $this->manyToOne($row, $root, $definition->getField('crossSelling'), $context);

        $this->translate($definition, $entity, $row, $root, $context, $definition->getTranslatedFields());
        $this->hydrateFields($definition, $entity, $root, $row, $context, $definition->getExtensionFields());

        return $entity;
    }
}
