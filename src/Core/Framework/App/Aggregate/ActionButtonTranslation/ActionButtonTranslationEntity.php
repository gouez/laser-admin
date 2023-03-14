<?php declare(strict_types=1);

namespace Laser\Core\Framework\App\Aggregate\ActionButtonTranslation;

use Laser\Core\Framework\App\Aggregate\ActionButton\ActionButtonEntity;
use Laser\Core\Framework\DataAbstractionLayer\Entity;
use Laser\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Laser\Core\Framework\Log\Package;
use Laser\Core\System\Language\LanguageEntity;

/**
 * @internal
 */
#[Package('core')]
class ActionButtonTranslationEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $appActionButtonId;

    /**
     * @var string
     */
    protected $languageId;

    /**
     * @var ActionButtonEntity|null
     */
    protected $appActionButton;

    /**
     * @var LanguageEntity|null
     */
    protected $language;

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getLanguageId(): string
    {
        return $this->languageId;
    }

    public function setLanguageId(string $languageId): void
    {
        $this->languageId = $languageId;
    }

    public function getAppActionButtonId(): string
    {
        return $this->appActionButtonId;
    }

    public function setAppActionButtonId(string $appActionButtonId): void
    {
        $this->appActionButtonId = $appActionButtonId;
    }

    public function getAppActionButton(): ?ActionButtonEntity
    {
        return $this->appActionButton;
    }

    public function setAppActionButton(?ActionButtonEntity $appActionButton): void
    {
        $this->appActionButton = $appActionButton;
    }

    public function getLanguage(): ?LanguageEntity
    {
        return $this->language;
    }

    public function setLanguage(?LanguageEntity $language): void
    {
        $this->language = $language;
    }
}
