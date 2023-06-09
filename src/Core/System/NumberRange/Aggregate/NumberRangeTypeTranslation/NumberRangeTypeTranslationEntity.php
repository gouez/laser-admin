<?php declare(strict_types=1);

namespace Laser\Core\System\NumberRange\Aggregate\NumberRangeTypeTranslation;

use Laser\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Laser\Core\Framework\DataAbstractionLayer\TranslationEntity;
use Laser\Core\Framework\Log\Package;
use Laser\Core\System\NumberRange\Aggregate\NumberRangeType\NumberRangeTypeEntity;

#[Package('checkout')]
class NumberRangeTypeTranslationEntity extends TranslationEntity
{
    use EntityCustomFieldsTrait;

    /**
     * @var string
     */
    protected $numberRangeTypeId;

    /**
     * @var string|null
     */
    protected $typeName;

    /**
     * @var NumberRangeTypeEntity|null
     */
    protected $numberRangeType;

    public function getNumberRangeTypeId(): string
    {
        return $this->numberRangeTypeId;
    }

    public function setNumberRangeTypeId(string $numberRangeTypeId): void
    {
        $this->numberRangeTypeId = $numberRangeTypeId;
    }

    public function getTypeName(): ?string
    {
        return $this->typeName;
    }

    public function setTypeName(?string $typeName): void
    {
        $this->typeName = $typeName;
    }

    public function getNumberRangeType(): ?NumberRangeTypeEntity
    {
        return $this->numberRangeType;
    }

    public function setNumberRangeType(?NumberRangeTypeEntity $numberRangeType): void
    {
        $this->numberRangeType = $numberRangeType;
    }
}
