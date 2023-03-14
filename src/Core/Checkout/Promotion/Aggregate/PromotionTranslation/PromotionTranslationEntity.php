<?php
declare(strict_types=1);

namespace Laser\Core\Checkout\Promotion\Aggregate\PromotionTranslation;

use Laser\Core\Checkout\Promotion\PromotionEntity;
use Laser\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Laser\Core\Framework\DataAbstractionLayer\TranslationEntity;
use Laser\Core\Framework\Log\Package;

#[Package('checkout')]
class PromotionTranslationEntity extends TranslationEntity
{
    use EntityCustomFieldsTrait;

    /**
     * @var string
     */
    protected $promotionId;

    /**
     * @var string|null
     */
    protected $name;

    /**
     * @var PromotionEntity|null
     */
    protected $promotion;

    public function getPromotionId(): string
    {
        return $this->promotionId;
    }

    public function setPromotionId(string $promotionId): void
    {
        $this->promotionId = $promotionId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getPromotion(): ?PromotionEntity
    {
        return $this->promotion;
    }

    public function setPromotion(PromotionEntity $promotion): void
    {
        $this->promotion = $promotion;
    }
}