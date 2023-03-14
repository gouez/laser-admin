<?php declare(strict_types=1);

namespace Laser\Core\Framework\Test\DataAbstractionLayer\Field;

use PHPUnit\Framework\TestCase;
use Laser\Core\Framework\DataAbstractionLayer\Field\TimeZoneField;
use Laser\Core\Framework\DataAbstractionLayer\FieldSerializer\TimeZoneFieldSerializer;
use Laser\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Laser\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Laser\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Laser\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Laser\Core\Framework\Uuid\Uuid;
use Laser\Core\Framework\Validation\WriteConstraintViolationException;

/**
 * @internal
 */
class TimeZoneFieldTest extends TestCase
{
    use KernelTestBehaviour;

    /**
     * @dataProvider validTimeZones
     */
    public function testTimeZoneSerializerTest(string $timeZone): void
    {
        $serializer = $this->getContainer()->get(TimeZoneFieldSerializer::class);

        $name = 'string_' . Uuid::randomHex();
        $data = new KeyValuePair($name, $timeZone, false);

        $val = $serializer->encode(
            new TimeZoneField($name, $name),
            new EntityExistence(null, [], true, false, false, []),
            $data,
            $this->createMock(WriteParameterBag::class)
        );

        $array = iterator_to_array($val);

        static::assertSame($timeZone, $array[$name]);
    }

    /**
     * @dataProvider inValidTimeZones
     */
    public function testInvalidTimeZone(string $timeZone): void
    {
        $serializer = $this->getContainer()->get(TimeZoneFieldSerializer::class);

        $name = 'string_' . Uuid::randomHex();
        $data = new KeyValuePair($name, $timeZone, false);

        static::expectException(WriteConstraintViolationException::class);

        iterator_to_array($serializer->encode(
            new TimeZoneField($name, $name),
            new EntityExistence(null, [], true, false, false, []),
            $data,
            $this->createMock(WriteParameterBag::class)
        ));
    }

    public function testNullable(): void
    {
        $serializer = $this->getContainer()->get(TimeZoneFieldSerializer::class);

        $name = 'string_' . Uuid::randomHex();
        $data = new KeyValuePair($name, null, false);

        $array = iterator_to_array($serializer->encode(
            new TimeZoneField($name, $name),
            new EntityExistence(null, [], true, false, false, []),
            $data,
            $this->createMock(WriteParameterBag::class)
        ));

        static::assertNull($array[$name]);
    }

    public static function validTimeZones(): array
    {
        return [
            ['UTC'],
            ['Europe/Berlin'],
        ];
    }

    public static function inValidTimeZones(): array
    {
        return [
            ['+01:00'],
            ['UTC+1'],
        ];
    }
}
