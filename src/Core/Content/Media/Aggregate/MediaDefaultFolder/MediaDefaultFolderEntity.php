<?php declare(strict_types=1);

namespace Laser\Core\Content\Media\Aggregate\MediaDefaultFolder;

use Laser\Core\Content\Media\Aggregate\MediaFolder\MediaFolderEntity;
use Laser\Core\Framework\DataAbstractionLayer\Entity;
use Laser\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Laser\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Laser\Core\Framework\Log\Package;

#[Package('content')]
class MediaDefaultFolderEntity extends Entity
{
    use EntityIdTrait;
    use EntityCustomFieldsTrait;

    /**
     * @var array<string>
     */
    protected $associationFields;

    /**
     * @var string
     */
    protected $entity;

    /**
     * @var MediaFolderEntity|null
     */
    protected $folder;

    public function getAssociationFields(): array
    {
        return $this->associationFields;
    }

    public function setAssociationFields(array $associationFields): void
    {
        $this->associationFields = $associationFields;
    }

    public function getEntity(): string
    {
        return $this->entity;
    }

    public function setEntity(string $entity): void
    {
        $this->entity = $entity;
    }

    public function getFolder(): ?MediaFolderEntity
    {
        return $this->folder;
    }

    public function setFolder(?MediaFolderEntity $folder): void
    {
        $this->folder = $folder;
    }
}
