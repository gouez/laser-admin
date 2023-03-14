<?php declare(strict_types=1);

namespace Laser\Core\Content\MailTemplate\Aggregate\MailTemplateType;

use Laser\Core\Content\MailTemplate\Aggregate\MailTemplateTypeTranslation\MailTemplateTypeTranslationCollection;
use Laser\Core\Content\MailTemplate\MailTemplateCollection;
use Laser\Core\Framework\DataAbstractionLayer\Entity;
use Laser\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Laser\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Laser\Core\Framework\Log\Package;

#[Package('sales-channel')]
class MailTemplateTypeEntity extends Entity
{
    use EntityIdTrait;
    use EntityCustomFieldsTrait;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $technicalName;

    /**
     * @var array|null
     */
    protected $availableEntities;

    /**
     * @var MailTemplateTypeTranslationCollection|null
     */
    protected $translations;

    /**
     * @var MailTemplateCollection|null
     */
    protected $mailTemplates;

    protected ?array $templateData;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getTechnicalName(): string
    {
        return $this->technicalName;
    }

    public function setTechnicalName(string $technicalName): void
    {
        $this->technicalName = $technicalName;
    }

    public function getTranslations(): ?MailTemplateTypeTranslationCollection
    {
        return $this->translations;
    }

    public function getAvailableEntities(): ?array
    {
        return $this->availableEntities;
    }

    public function setAvailableEntities(?array $availableEntities): void
    {
        $this->availableEntities = $availableEntities;
    }

    public function setTranslations(MailTemplateTypeTranslationCollection $translations): void
    {
        $this->translations = $translations;
    }

    public function getMailTemplates(): ?MailTemplateCollection
    {
        return $this->mailTemplates;
    }

    public function setMailTemplates(MailTemplateCollection $mailTemplates): void
    {
        $this->mailTemplates = $mailTemplates;
    }

    public function getTemplateData(): ?array
    {
        return $this->templateData;
    }

    public function setTemplateData(?array $templateData): void
    {
        $this->templateData = $templateData;
    }
}