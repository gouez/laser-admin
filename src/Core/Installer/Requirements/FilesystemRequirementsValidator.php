<?php declare(strict_types=1);

namespace Laser\Core\Installer\Requirements;

use Laser\Core\Framework\Log\Package;
use Laser\Core\Installer\Requirements\Struct\PathCheck;
use Laser\Core\Installer\Requirements\Struct\RequirementCheck;
use Laser\Core\Installer\Requirements\Struct\RequirementsCheckCollection;

/**
 * @internal
 */
#[Package('core')]
class FilesystemRequirementsValidator implements RequirementsValidatorInterface
{
    private const NEEDED_PATHS = [
        '.',
        'var/log/',
        'var/cache/',
        'public/',
        'config/jwt/',
    ];

    public function __construct(private readonly string $projectDir)
    {
    }

    public function validateRequirements(RequirementsCheckCollection $checks): RequirementsCheckCollection
    {
        foreach (self::NEEDED_PATHS as $path) {
            $absolutePath = $this->projectDir . '/' . $path;

            $checks->add(new PathCheck(
                $path,
                $this->existsAndIsWritable($absolutePath) ? RequirementCheck::STATUS_SUCCESS : RequirementCheck::STATUS_ERROR
            ));
        }

        return $checks;
    }

    private function existsAndIsWritable(string $path): bool
    {
        return file_exists($path) && is_readable($path) && is_writable($path);
    }
}
