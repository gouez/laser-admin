<?php declare(strict_types=1);

namespace Laser\Core\Framework\Test\DataAbstractionLayer;

use PHPUnit\Framework\TestCase;
use Laser\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Laser\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;

/**
 * @internal
 */
class EntityDefinitionHasSinceTest extends TestCase
{
    use KernelTestBehaviour;

    public function testAllDefinitionsHasSince(): void
    {
        $service = $this->getContainer()->get(DefinitionInstanceRegistry::class);

        $definitionsWithoutSince = [];

        foreach ($service->getDefinitions() as $definition) {
            if ($definition->since() === null) {
                $definitionsWithoutSince[] = $definition->getEntityName();
            }
        }

        static::assertCount(0, $definitionsWithoutSince, sprintf('Following definitions does not have a since version: %s', implode(',', $definitionsWithoutSince)));
    }
}
