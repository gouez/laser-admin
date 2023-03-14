<?php declare(strict_types=1);

namespace Laser\Core\System\CustomEntity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Laser\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Laser\Core\Framework\DataAbstractionLayer\EntityRepository;
use Laser\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEventFactory;
use Laser\Core\Framework\DataAbstractionLayer\Read\EntityReaderInterface;
use Laser\Core\Framework\DataAbstractionLayer\Search\EntityAggregatorInterface;
use Laser\Core\Framework\DataAbstractionLayer\Search\EntitySearcherInterface;
use Laser\Core\Framework\DataAbstractionLayer\VersionManager;
use Laser\Core\Framework\Log\Package;
use Laser\Core\System\CustomEntity\Schema\DynamicEntityDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @internal
 */
#[Package('core')]
class CustomEntityRegistrar
{
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    public function register(): void
    {
        if (!$this->container->get(Connection::class)->isConnected()) {
            return;
        }

        try {
            $entities = $this->container->get(Connection::class)->fetchAllAssociative('
                SELECT custom_entity.name, custom_entity.fields, custom_entity.flags
                FROM custom_entity
                    LEFT JOIN app ON app.id = custom_entity.app_id
                WHERE custom_entity.app_id IS NULL OR app.active = 1
            ');
        } catch (Exception) {
            // kernel booted without database connection, or booted for migration and custom entity table not created yet
            return;
        }

        $definitions = [];
        $registry = $this->container->get(DefinitionInstanceRegistry::class);

        foreach ($entities as $entity) {
            $fields = json_decode((string) $entity['fields'], true, 512, \JSON_THROW_ON_ERROR);
            $flags = json_decode((string) $entity['flags'], true, 512, \JSON_THROW_ON_ERROR) ?? [];

            $definition = DynamicEntityDefinition::create($entity['name'], $fields, $flags, $this->container);

            $definitions[] = $definition;

            $this->container->set($definition->getEntityName(), $definition);
            $this->container->set($definition->getEntityName() . '.repository', $this->createRepository($definition));
            $registry->register($definition, $definition->getEntityName());
        }

        foreach ($definitions as $definition) {
            // triggers field generation to generate reverse foreign keys, translation definitions and mapping definitions
            $definition->getFields();
        }
    }

    private function createRepository(DynamicEntityDefinition $definition): EntityRepository
    {
        return new EntityRepository(
            $definition,
            $this->container->get(EntityReaderInterface::class),
            $this->container->get(VersionManager::class),
            $this->container->get(EntitySearcherInterface::class),
            $this->container->get(EntityAggregatorInterface::class),
            $this->container->get('event_dispatcher'),
            $this->container->get(EntityLoadedEventFactory::class)
        );
    }
}
