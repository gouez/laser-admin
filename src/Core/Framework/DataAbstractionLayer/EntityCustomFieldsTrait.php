<?php
declare(strict_types=1);

namespace Laser\Core\Framework\DataAbstractionLayer;

use Laser\Core\Framework\Log\Package;

#[Package('core')]
trait EntityCustomFieldsTrait
{
    /**
     * @var array<mixed>|null
     */
    protected $customFields;

    /**
     * @return array<mixed>|null
     */
    public function getCustomFields(): ?array
    {
        return $this->customFields;
    }

    /**
     * @param array<mixed>|null $customFields
     */
    public function setCustomFields(?array $customFields): void
    {
        $this->customFields = $customFields;
    }
}
