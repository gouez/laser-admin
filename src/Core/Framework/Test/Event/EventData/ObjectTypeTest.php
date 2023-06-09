<?php declare(strict_types=1);

namespace Laser\Core\Framework\Test\Event\EventData;

use PHPUnit\Framework\TestCase;
use Laser\Core\Framework\Event\EventData\ObjectType;
use Laser\Core\Framework\Event\EventData\ScalarValueType;

/**
 * @internal
 */
class ObjectTypeTest extends TestCase
{
    public function testToArray(): void
    {
        $expected = [
            'type' => 'object',
            'data' => [
                'myBool' => [
                    'type' => 'bool',
                ],
                'myString' => [
                    'type' => 'string',
                ],
            ],
        ];

        static::assertEquals(
            $expected,
            (new ObjectType())
                ->add('myBool', new ScalarValueType(ScalarValueType::TYPE_BOOL))
                ->add('myString', new ScalarValueType(ScalarValueType::TYPE_STRING))
                ->toArray()
        );
    }
}
