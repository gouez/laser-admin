<?php declare(strict_types=1);

namespace Laser\Core\System\Snippet\Aggregate\SnippetSet;

use Laser\Core\Framework\DataAbstractionLayer\EntityCollection;
use Laser\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<SnippetSetEntity>
 */
#[Package('system-settings')]
class SnippetSetCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'snippet_set_collection';
    }

    protected function getExpectedClass(): string
    {
        return SnippetSetEntity::class;
    }
}
