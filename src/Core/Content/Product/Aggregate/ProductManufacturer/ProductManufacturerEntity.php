<?php declare(strict_types=1);

namespace Laser\Core\Content\Product\Aggregate\ProductManufacturer;

use Laser\Core\Content\Media\MediaEntity;
use Laser\Core\Content\Product\Aggregate\ProductManufacturerTranslation\ProductManufacturerTranslationCollection;
use Laser\Core\Content\Product\ProductCollection;
use Laser\Core\Framework\DataAbstractionLayer\Entity;
use Laser\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Laser\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Laser\Core\Framework\Log\Package;

#[Package('inventory')]
class ProductManufacturerEntity extends Entity
{
    use EntityIdTrait;
    use EntityCustomFieldsTrait;

    /**
     * @var string|null
     */
    protected $mediaId;

    /**
     * @var string|null
     */
    protected $name;

    /**
     * @var string|null
     */
    protected $link;

    /**
     * @var string|null
     */
    protected $description;

    /**
     * @var MediaEntity|null
     */
    protected $media;

    /**
     * @var ProductManufacturerTranslationCollection|null
     */
    protected $translations;

    /**
     * @var ProductCollection|null
     */
    protected $products;

    public function getMediaId(): ?string
    {
        return $this->mediaId;
    }

    public function setMediaId(?string $mediaId): void
    {
        $this->mediaId = $mediaId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): void
    {
        $this->link = $link;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getMedia(): ?MediaEntity
    {
        return $this->media;
    }

    public function setMedia(MediaEntity $media): void
    {
        $this->media = $media;
    }

    public function getTranslations(): ?ProductManufacturerTranslationCollection
    {
        return $this->translations;
    }

    public function setTranslations(ProductManufacturerTranslationCollection $translations): void
    {
        $this->translations = $translations;
    }

    public function getProducts(): ?ProductCollection
    {
        return $this->products;
    }

    public function setProducts(ProductCollection $products): void
    {
        $this->products = $products;
    }
}