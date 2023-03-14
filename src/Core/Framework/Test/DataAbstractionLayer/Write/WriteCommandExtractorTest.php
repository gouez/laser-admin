<?php declare(strict_types=1);

namespace DataAbstractionLayer\Write;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Laser\Core\Defaults;
use Laser\Core\Framework\Context;
use Laser\Core\Framework\DataAbstractionLayer\Dbal\EntityDefinitionQueryHelper;
use Laser\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Laser\Core\Framework\DataAbstractionLayer\Write\EntityWriter;
use Laser\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Laser\Core\Framework\Test\DataAbstractionLayer\Write\Entity\DefaultsChildDefinition;
use Laser\Core\Framework\Test\DataAbstractionLayer\Write\Entity\DefaultsChildTranslationDefinition;
use Laser\Core\Framework\Test\DataAbstractionLayer\Write\Entity\DefaultsDefinition;
use Laser\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Laser\Core\Framework\Uuid\Uuid;

/**
 * @internal
 */
class WriteCommandExtractorTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function setUp(): void
    {
        $this->stopTransactionAfter();
        $connection = $this->getContainer()->get(Connection::class);

        $connection->executeStatement(DefaultsDefinition::SCHEMA);
        $connection->executeStatement(DefaultsChildDefinition::SCHEMA);
        $connection->executeStatement(DefaultsChildTranslationDefinition::SCHEMA);

        $this->startTransactionBefore();

        $defaultsDefinition = new DefaultsDefinition();
        $definitions = $this->getContainer()->get(DefinitionInstanceRegistry::class);
        $definitions->register($defaultsDefinition);
        $definitions->register(new DefaultsChildDefinition());
        $definitions->register(new DefaultsChildTranslationDefinition());
    }

    public function tearDown(): void
    {
        $this->stopTransactionAfter();
        $connection = $this->getContainer()->get(Connection::class);

        $connection->executeStatement('DROP TABLE IF EXISTS ' . EntityDefinitionQueryHelper::escape('defaults_child_translation'));
        $connection->executeStatement('DROP TABLE IF EXISTS ' . EntityDefinitionQueryHelper::escape('defaults_child'));
        $connection->executeStatement('DROP TABLE IF EXISTS ' . EntityDefinitionQueryHelper::escape('defaults'));

        $this->startTransactionBefore();
    }

    public function testWriteWithNestedDefaults(): void
    {
        $context = WriteContext::createFromContext(Context::createDefaultContext());
        $writer = $this->getContainer()->get(EntityWriter::class);

        $id = Uuid::randomHex();
        $writeResults = $writer->insert($this->getContainer()->get(DefaultsDefinition::class), [
            ['id' => $id],
        ], $context);

        static::assertCount(3, $writeResults);

        static::assertCount(1, $writeResults['defaults']);
        $defaultsWriteResult = $writeResults['defaults'][0];
        static::assertTrue($defaultsWriteResult->getPayload()['active']);

        static::assertCount(1, $writeResults['defaults_child']);
        $defaultsChildWriteResult = $writeResults['defaults_child'][0];
        static::assertSame($id, $defaultsChildWriteResult->getPayload()['defaultsId']);
        static::assertSame('Default foo', $defaultsChildWriteResult->getPayload()['foo']);
        $defaultsChildId = $defaultsChildWriteResult->getPayload()['id'];

        static::assertCount(1, $writeResults['defaults_child_translation']);
        $defaultsChildTranslationWriteResult = $writeResults['defaults_child_translation'][0];
        static::assertSame($defaultsChildId, $defaultsChildTranslationWriteResult->getPayload()['defaultsChildId']);
        static::assertSame(Defaults::LANGUAGE_SYSTEM, $defaultsChildTranslationWriteResult->getPayload()['languageId']);
        static::assertSame('Default name', $defaultsChildTranslationWriteResult->getPayload()['name']);
    }
}