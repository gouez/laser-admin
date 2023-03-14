<?php declare(strict_types=1);

namespace Laser\Core\System\NumberRange\Aggregate\NumberRangeSalesChannel;

use Laser\Core\Framework\DataAbstractionLayer\Entity;
use Laser\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Laser\Core\Framework\Log\Package;
use Laser\Core\System\NumberRange\Aggregate\NumberRangeType\NumberRangeTypeEntity;
use Laser\Core\System\NumberRange\NumberRangeEntity;
use Laser\Core\System\SalesChannel\SalesChannelEntity;

#[Package('checkout')]
class NumberRangeSalesChannelEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $numberRangeId;

    /**
     * @var string
     */
    protected $salesChannelId;

    /**
     * @var string
     */
    protected $numberRangeTypeId;

    /**
     * @var NumberRangeEntity|null
     */
    protected $numberRange;

    /**
     * @var SalesChannelEntity|null
     */
    protected $salesChannel;

    /**
     * @var NumberRangeTypeEntity|null
     */
    protected $numberRangeType;

    public function getNumberRangeId(): string
    {
        return $this->numberRangeId;
    }

    public function setNumberRangeId(string $numberRangeId): void
    {
        $this->numberRangeId = $numberRangeId;
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    public function setSalesChannelId(string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }

    public function getNumberRangeTypeId(): string
    {
        return $this->numberRangeTypeId;
    }

    public function setNumberRangeTypeId(string $numberRangeTypeId): void
    {
        $this->numberRangeTypeId = $numberRangeTypeId;
    }

    public function getNumberRange(): ?NumberRangeEntity
    {
        return $this->numberRange;
    }

    public function setNumberRange(NumberRangeEntity $numberRange): void
    {
        $this->numberRange = $numberRange;
    }

    public function getSalesChannel(): ?SalesChannelEntity
    {
        return $this->salesChannel;
    }

    public function setSalesChannel(SalesChannelEntity $salesChannel): void
    {
        $this->salesChannel = $salesChannel;
    }

    public function getNumberRangeType(): ?NumberRangeTypeEntity
    {
        return $this->numberRangeType;
    }

    public function setNumberRangeType(NumberRangeTypeEntity $numberRangeType): void
    {
        $this->numberRangeType = $numberRangeType;
    }
}
