<?php declare(strict_types=1);

namespace Laser\Core\Framework\App\Lifecycle;

use Laser\Core\Framework\App\AppEntity;
use Laser\Core\Framework\App\Cms\CmsExtensions;
use Laser\Core\Framework\App\FlowAction\FlowAction;
use Laser\Core\Framework\App\Manifest\Manifest;
use Laser\Core\Framework\Log\Package;
use Laser\Core\System\CustomEntity\Xml\CustomEntityXmlSchema;

/**
 * @internal
 */
#[Package('core')]
abstract class AbstractAppLoader
{
    abstract public function getDecorated(): AbstractAppLoader;

    /**
     * @return Manifest[]
     */
    abstract public function load(): array;

    abstract public function getIcon(Manifest $app): ?string;

    /**
     * @return array<mixed>|null
     */
    abstract public function getConfiguration(AppEntity $app): ?array;

    abstract public function deleteApp(string $technicalName): void;

    abstract public function getCmsExtensions(AppEntity $app): ?CmsExtensions;

    abstract public function getAssetPathForAppPath(string $appPath): string;

    abstract public function getEntities(AppEntity $app): ?CustomEntityXmlSchema;

    abstract public function getFlowActions(AppEntity $app): ?FlowAction;

    abstract public function getFlowActionIcon(?string $iconName, FlowAction $flowAction): ?string;

    /**
     * @return array<string, string>
     */
    abstract public function getSnippets(AppEntity $app): array;
}
