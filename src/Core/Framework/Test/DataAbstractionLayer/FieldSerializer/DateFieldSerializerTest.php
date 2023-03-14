<?php declare(strict_types=1);

namespace Laser\Core\Framework\Test\DataAbstractionLayer\FieldSerializer;

use PHPUnit\Framework\TestCase;
use Laser\Core\Framework\Context;
use Laser\Core\Framework\DataAbstractionLayer\Field\DateField;
use Laser\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Laser\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Laser\Core\Framework\DataAbstractionLayer\FieldSerializer\DateFieldSerializer;
use Laser\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommandQueue;
use Laser\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Laser\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Laser\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Laser\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Laser\Core\Framework\Test\DataAbstractionLayer\Field\DataAbstractionLayerFieldTestBehaviour;
use Laser\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition\DateDefinition;
use Laser\Core\Framework\Test\TestCaseBase\CacheTestBehaviour;
use Laser\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Laser\Core\Framework\Validation\WriteConstraintViolationException;

/**
 * @internal
 */
class DateFieldSerializerTest extends TestCase
{
    use KernelTestBehaviour;
    use CacheTestBehaviour;
    use DataAbstractionLayerFieldTestBehaviour;

    private DateFieldSerializer $serializer;

    private DateField $field;

    private EntityExistence $existence;

    private WriteParameterBag $parameters;

    protected function setUp(): void
    {
        $this->serializer = $this->getContainer()->get(DateFieldSerializer::class);
        $this->field = new DateField('date', 'date');
        $this->field->addFlags(new ApiAware(), new Required());

        $definition = $this->registerDefinition(DateDefinition::class);
        $this->existence = new EntityExistence($definition->getEntityName(), [], false, false, false, []);

        $this->parameters = new WriteParameterBag(
            $definition,
            WriteContext::createFromContext(Context::createDefaultContext()),
            '',
            new WriteCommandQueue()
        );
    }

    public static function serializerProvider(): array
    {
        return [
            [
                [
                    new \DateTime('2020-05-15 00:00:00', new \DateTimeZone('UTC')),
                    new \DateTime('2020-05-15 00:00:00', new \DateTimeZone('UTC')),
                ],
            ],
            [
                [
                    new \DateTime('2099-05-18 00:00:00', new \DateTimeZone('UTC')),
                    new \DateTime('2099-05-18 00:00:00', new \DateTimeZone('UTC')),
                ],
            ],
            [
                [
                    new \DateTime('2020-05-15 22:00:00', new \DateTimeZone('EDT')),
                    new \DateTime('2020-05-16 00:00:00', new \DateTimeZone('UTC')),
                ],
            ],
        ];
    }

    /**
     * @dataProvider serializerProvider
     */
    public function testSerializer($input): void
    {
        $kvPair = new KeyValuePair('date', $input[0], true);
        $encoded = $this->serializer->encode($this->field, $this->existence, $kvPair, $this->parameters)->current();
        $decoded = $this->serializer->decode($this->field, $encoded);

        static::assertEquals($input[1], $decoded, 'Output should be ' . print_r($input[1], true));
    }

    public function testSerializerValidatesRequiredField(): void
    {
        $kvPair = new KeyValuePair('date', null, true);
        $this->field->removeFlag(Required::class);

        $encoded = $this->serializer->encode($this->field, $this->existence, $kvPair, $this->parameters)->current();
        $decoded = $this->serializer->decode($this->field, $encoded);

        static::assertNull($decoded);

        $this->field->addFlags(new Required());
        static::expectException(WriteConstraintViolationException::class);
        $this->serializer->encode($this->field, $this->existence, $kvPair, $this->parameters)->current();
    }
}
