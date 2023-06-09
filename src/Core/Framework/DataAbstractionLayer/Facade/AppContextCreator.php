<?php declare(strict_types=1);

namespace Laser\Core\Framework\DataAbstractionLayer\Facade;

use Doctrine\DBAL\Connection;
use Laser\Core\Framework\Api\Context\AdminApiSource;
use Laser\Core\Framework\Context;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Script\Execution\Hook;
use Laser\Core\Framework\Script\Execution\Script;
use Laser\Core\Framework\Script\Execution\ScriptAppInformation;
use Laser\Core\Framework\Uuid\Uuid;

/**
 * @internal
 */
#[Package('core')]
class AppContextCreator
{
    /**
     * @var array<string, AdminApiSource>
     */
    private array $appSources = [];

    public function __construct(private readonly Connection $connection)
    {
    }

    public function getAppContext(Hook $hook, Script $script): Context
    {
        $scriptAppInformation = $script->getScriptAppInformation();
        if (!$scriptAppInformation) {
            return $hook->getContext();
        }

        return new Context(
            $this->getAppContextSource($scriptAppInformation),
            $hook->getContext()->getRuleIds(),
            $hook->getContext()->getCurrencyId(),
            $hook->getContext()->getLanguageIdChain(),
            $hook->getContext()->getVersionId(),
            $hook->getContext()->getCurrencyFactor(),
            $hook->getContext()->considerInheritance(),
            $hook->getContext()->getTaxState(),
            $hook->getContext()->getRounding()
        );
    }

    private function getAppContextSource(ScriptAppInformation $scriptAppInformation): AdminApiSource
    {
        if (\array_key_exists($scriptAppInformation->getAppId(), $this->appSources)) {
            return $this->appSources[$scriptAppInformation->getAppId()];
        }

        $privileges = $this->fetchPrivileges($scriptAppInformation->getAppId());
        $source = new AdminApiSource(null, $scriptAppInformation->getIntegrationId());
        $source->setIsAdmin(false);
        $source->setPermissions($privileges);

        return $this->appSources[$scriptAppInformation->getAppId()] = $source;
    }

    private function fetchPrivileges(string $appId): array
    {
        $privileges = $this->connection->fetchOne('
            SELECT `acl_role`.`privileges` AS `privileges`
            FROM `acl_role`
            INNER JOIN `app` ON `app`.`acl_role_id` = `acl_role`.`id`
            WHERE `app`.`id` = :appId
        ', ['appId' => Uuid::fromHexToBytes($appId)]);

        if (!$privileges) {
            throw new \RuntimeException(sprintf('Privileges for app with id "%s" not found.', $appId));
        }

        return json_decode((string) $privileges, true, 512, \JSON_THROW_ON_ERROR);
    }
}
