<?php declare(strict_types=1);

namespace Laser\Core\Framework\App\Aggregate\FlowActionTranslation;

use Laser\Core\Framework\App\Aggregate\FlowAction\AppFlowActionEntity;
use Laser\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Laser\Core\Framework\DataAbstractionLayer\TranslationEntity;
use Laser\Core\Framework\Log\Package;

#[Package('core')]
class AppFlowActionTranslationEntity extends TranslationEntity
{
    use EntityCustomFieldsTrait;

    protected ?string $label = null;

    protected ?string $description = null;

    protected ?string $headline = null;

    protected string $appFlowActionId;

    protected ?AppFlowActionEntity $appFlowAction = null;

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): void
    {
        $this->label = $label;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getHeadline(): ?string
    {
        return $this->headline;
    }

    public function setHeadline(?string $headline): void
    {
        $this->headline = $headline;
    }

    public function getAppFlowActionId(): string
    {
        return $this->appFlowActionId;
    }

    public function setAppFlowActionId(string $appFlowActionId): void
    {
        $this->appFlowActionId = $appFlowActionId;
    }

    public function getAppFlowAction(): ?AppFlowActionEntity
    {
        return $this->appFlowAction;
    }

    public function setAppFlowAction(?AppFlowActionEntity $appFlowAction): void
    {
        $this->appFlowAction = $appFlowAction;
    }
}
