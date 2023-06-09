<?php declare(strict_types=1);

namespace Laser\Core\System\SalesChannel\Aggregate\SalesChannelTypeTranslation;

use Laser\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Laser\Core\Framework\DataAbstractionLayer\TranslationEntity;
use Laser\Core\Framework\Log\Package;
use Laser\Core\System\SalesChannel\Aggregate\SalesChannelType\SalesChannelTypeEntity;

#[Package('sales-channel')]
class SalesChannelTypeTranslationEntity extends TranslationEntity
{
    use EntityCustomFieldsTrait;

    /**
     * @var string
     */
    protected $salesChannelTypeId;

    /**
     * @var string|null
     */
    protected $name;

    /**
     * @var string|null
     */
    protected $manufacturer;

    /**
     * @var string|null
     */
    protected $description;

    /**
     * @var string|null
     */
    protected $descriptionLong;

    /**
     * @var SalesChannelTypeEntity|null
     */
    protected $salesChannelType;

    public function getSalesChannelTypeId(): string
    {
        return $this->salesChannelTypeId;
    }

    public function setSalesChannelTypeId(string $salesChannelTypeId): void
    {
        $this->salesChannelTypeId = $salesChannelTypeId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getManufacturer(): ?string
    {
        return $this->manufacturer;
    }

    public function setManufacturer(?string $manufacturer): void
    {
        $this->manufacturer = $manufacturer;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getDescriptionLong(): ?string
    {
        return $this->descriptionLong;
    }

    public function setDescriptionLong(?string $descriptionLong): void
    {
        $this->descriptionLong = $descriptionLong;
    }

    public function getSalesChannelType(): ?SalesChannelTypeEntity
    {
        return $this->salesChannelType;
    }

    public function setSalesChannelType(SalesChannelTypeEntity $salesChannelType): void
    {
        $this->salesChannelType = $salesChannelType;
    }
}
