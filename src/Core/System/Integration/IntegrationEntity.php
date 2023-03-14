<?php declare(strict_types=1);

namespace Laser\Core\System\Integration;

use Laser\Core\Framework\Api\Acl\Role\AclRoleCollection;
use Laser\Core\Framework\App\AppEntity;
use Laser\Core\Framework\DataAbstractionLayer\Entity;
use Laser\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Laser\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Laser\Core\Framework\Feature;
use Laser\Core\Framework\Log\Package;

#[Package('system-settings')]
class IntegrationEntity extends Entity
{
    use EntityIdTrait;
    use EntityCustomFieldsTrait;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $accessKey;

    /**
     * @var string
     */
    protected $secretAccessKey;

    /**
     * @var bool
     */
    protected $admin;

    /**
     * @var \DateTimeInterface|null
     */
    protected $lastUsageAt;

    /**
     * @var AppEntity|null
     */
    protected $app;

    /**
     * @var AclRoleCollection|null
     */
    protected $aclRoles;

    /**
     * @deprecated tag:v6.6.0 - Will be removed
     *
     * @var bool
     */
    protected $writeAccess;

    protected ?\DateTimeInterface $deletedAt = null;

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getAccessKey(): string
    {
        return $this->accessKey;
    }

    public function setAccessKey(string $accessKey): void
    {
        $this->accessKey = $accessKey;
    }

    public function getSecretAccessKey(): string
    {
        return $this->secretAccessKey;
    }

    public function setSecretAccessKey(string $secretAccessKey): void
    {
        $this->secretAccessKey = $secretAccessKey;
    }

    public function getLastUsageAt(): ?\DateTimeInterface
    {
        return $this->lastUsageAt;
    }

    public function setLastUsageAt(\DateTimeInterface $lastUsageAt): void
    {
        $this->lastUsageAt = $lastUsageAt;
    }

    public function getApp(): ?AppEntity
    {
        return $this->app;
    }

    public function setApp(?AppEntity $app): void
    {
        $this->app = $app;
    }

    public function getAclRoles(): ?AclRoleCollection
    {
        return $this->aclRoles;
    }

    public function setAclRoles(AclRoleCollection $aclRoles): void
    {
        $this->aclRoles = $aclRoles;
    }

    public function getAdmin(): bool
    {
        return $this->admin;
    }

    public function setAdmin(bool $admin): void
    {
        $this->admin = $admin;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(\DateTimeInterface $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
    }

    /**
     * @deprecated tag:v6.6.0 - Will be removed
     */
    public function getWriteAccess(): bool
    {
        Feature::triggerDeprecationOrThrow(
            'v6.6.0.0',
            Feature::deprecatedMethodMessage(__CLASS__, __METHOD__, 'v6.6.0.0')
        );

        return $this->writeAccess;
    }

    /**
     * @deprecated tag:v6.6.0 - Will be removed
     */
    public function setWriteAccess(bool $writeAccess): void
    {
        Feature::triggerDeprecationOrThrow(
            'v6.6.0.0',
            Feature::deprecatedMethodMessage(__CLASS__, __METHOD__, 'v6.6.0.0')
        );

        $this->writeAccess = $writeAccess;
    }
}
