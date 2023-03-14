<?php declare(strict_types=1);

namespace Laser\Core\Content\Flow\DataAbstractionLayer\FieldSerializer;

use Laser\Core\Content\Flow\DataAbstractionLayer\Field\FlowTemplateConfigField;
use Laser\Core\Framework\DataAbstractionLayer\Exception\InvalidSerializerFieldException;
use Laser\Core\Framework\DataAbstractionLayer\Field\Field;
use Laser\Core\Framework\DataAbstractionLayer\FieldSerializer\JsonFieldSerializer;
use Laser\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Laser\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Laser\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Validation\Constraint\Uuid;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Type;

/**
 * @internal
 */
#[Package('business-ops')]
class FlowTemplateConfigFieldSerializer extends JsonFieldSerializer
{
    public function encode(
        Field $field,
        EntityExistence $existence,
        KeyValuePair $data,
        WriteParameterBag $parameters
    ): \Generator {
        if (!$field instanceof FlowTemplateConfigField) {
            throw new InvalidSerializerFieldException(FlowTemplateConfigField::class, $field);
        }

        $this->validateIfNeeded($field, $existence, $data, $parameters);

        $value = $data->getValue();

        if (!\is_array($value)) {
            yield $field->getStorageName() => null;

            return;
        }

        $value = array_merge([
            'description' => null,
            'sequences' => [],
        ], $value);

        $sequences = $value['sequences'];

        $value['sequences'] = array_map(fn ($item) => array_merge([
            'parentId' => null,
            'ruleId' => null,
            'position' => 1,
            'displayGroup' => 1,
            'trueCase' => 0,
        ], $item), $sequences);

        yield $field->getStorageName() => JsonFieldSerializer::encodeJson($value);
    }

    protected function getConstraints(Field $field): array
    {
        return [
            new Collection([
                'allowExtraFields' => true,
                'allowMissingFields' => false,
                'fields' => [
                    'eventName' => [new NotBlank(), new Type('string')],
                    'description' => [new Type('string')],
                    'sequences' => [
                        [
                            new Optional(
                                new Collection([
                                    'allowExtraFields' => true,
                                    'allowMissingFields' => false,
                                    'fields' => [
                                        'id' => [new NotBlank(), new Uuid()],
                                        'actionName' => [new NotBlank(), new Type('string')],
                                        'parentId' => [new Uuid()],
                                        'ruleId' => [new Uuid()],
                                        'position' => [new Type('numeric')],
                                        'trueCase' => [new Type('boolean')],
                                        'displayGroup' => [new Type('numeric')],
                                        'config' => [new Type('array')],
                                    ],
                                ])
                            ),
                        ],
                    ],
                ],
            ]),
        ];
    }
}
