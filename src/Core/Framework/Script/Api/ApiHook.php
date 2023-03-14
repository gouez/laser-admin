<?php declare(strict_types=1);

namespace Laser\Core\Framework\Script\Api;

use Laser\Core\Framework\Context;
use Laser\Core\Framework\DataAbstractionLayer\Facade\RepositoryFacadeHookFactory;
use Laser\Core\Framework\DataAbstractionLayer\Facade\RepositoryWriterFacadeHookFactory;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Script\Execution\Awareness\ScriptResponseAwareTrait;
use Laser\Core\Framework\Script\Execution\Awareness\StoppableHook;
use Laser\Core\Framework\Script\Execution\Awareness\StoppableHookTrait;
use Laser\Core\Framework\Script\Execution\Hook;
use Laser\Core\System\SystemConfig\Facade\SystemConfigFacadeHookFactory;

/**
 * Triggered when the api endpoint /api/script/{hook} is called
 *
 * @hook-use-case custom_endpoint
 *
 * @since 6.4.9.0
 */
#[Package('core')]
class ApiHook extends Hook implements StoppableHook
{
    use StoppableHookTrait;
    use ScriptResponseAwareTrait;

    final public const HOOK_NAME = 'api-{hook}';

    public function __construct(
        private readonly string $name,
        private readonly array $request,
        Context $context
    ) {
        parent::__construct($context);
    }

    public function getInternalName(): string
    {
        return $this->name;
    }

    public function getRequest(): array
    {
        return $this->request;
    }

    public function getName(): string
    {
        return \str_replace(
            ['{hook}'],
            [$this->name],
            self::HOOK_NAME
        );
    }

    public static function getServiceIds(): array
    {
        return [
            RepositoryFacadeHookFactory::class,
            RepositoryWriterFacadeHookFactory::class,
            SystemConfigFacadeHookFactory::class,
            ScriptResponseFactoryFacadeHookFactory::class,
        ];
    }
}
