<?php
declare(strict_types=1);

namespace Laser\Core\System\CustomEntity;

use Laser\Core\Framework\App\AppEntity;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Plugin\PluginEntity;
use Laser\Core\System\CustomEntity\Schema\CustomEntityPersister;
use Laser\Core\System\CustomEntity\Schema\CustomEntitySchemaUpdater;
use Laser\Core\System\CustomEntity\Xml\Config\AdminUi\AdminUiXmlSchema;
use Laser\Core\System\CustomEntity\Xml\Config\CustomEntityEnrichmentService;
use Laser\Core\System\CustomEntity\Xml\CustomEntityXmlSchema;
use Laser\Core\System\CustomEntity\Xml\CustomEntityXmlSchemaValidator;

/**
 * @internal
 */
#[Package('core')]
class CustomEntityLifecycleService
{
    public function __construct(
        private readonly CustomEntityPersister $customEntityPersister,
        private readonly CustomEntitySchemaUpdater $customEntitySchemaUpdater,
        private readonly CustomEntityEnrichmentService $customEntityEnrichmentService,
        private readonly CustomEntityXmlSchemaValidator $customEntityXmlSchemaValidator,
        private readonly string $projectDir
    ) {
    }

    public function updatePlugin(string $pluginId, string $pluginPath): ?CustomEntityXmlSchema
    {
        return $this->update(
            sprintf(
                '%s/%s/src/Resources/',
                $this->projectDir,
                $pluginPath,
            ),
            PluginEntity::class,
            $pluginId
        );
    }

    public function updateApp(string $appId, string $appPath): ?CustomEntityXmlSchema
    {
        return $this->update(
            sprintf(
                '%s/%s/Resources/',
                $this->projectDir,
                $appPath,
            ),
            AppEntity::class,
            $appId
        );
    }

    private function update(string $pathToCustomEntityFile, string $extensionEntityType, string $extensionId): ?CustomEntityXmlSchema
    {
        $customEntityXmlSchema = $this->getXmlSchema($pathToCustomEntityFile);
        if ($customEntityXmlSchema === null) {
            return null;
        }

        $customEntityXmlSchema = $this->customEntityEnrichmentService->enrich(
            $customEntityXmlSchema,
            $this->getAdminUiXmlSchema($pathToCustomEntityFile),
        );

        $this->customEntityPersister->update($customEntityXmlSchema->toStorage(), $extensionEntityType, $extensionId);
        $this->customEntitySchemaUpdater->update();

        return $customEntityXmlSchema;
    }

    private function getXmlSchema(string $pathToCustomEntityFile): ?CustomEntityXmlSchema
    {
        $filePath = $pathToCustomEntityFile . CustomEntityXmlSchema::FILENAME;
        if (!file_exists($filePath)) {
            return null;
        }

        $customEntityXmlSchema = CustomEntityXmlSchema::createFromXmlFile($filePath);
        $this->customEntityXmlSchemaValidator->validate($customEntityXmlSchema);

        return $customEntityXmlSchema;
    }

    private function getAdminUiXmlSchema(string $pathToCustomEntityFile): ?AdminUiXmlSchema
    {
        $configPath = $pathToCustomEntityFile . 'config/' . AdminUiXmlSchema::FILENAME;

        if (!file_exists($configPath)) {
            return null;
        }

        return AdminUiXmlSchema::createFromXmlFile($configPath);
    }
}
