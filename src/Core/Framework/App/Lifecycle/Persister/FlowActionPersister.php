<?php declare(strict_types=1);

namespace Laser\Core\Framework\App\Lifecycle\Persister;

use Doctrine\DBAL\Connection;
use Laser\Core\Framework\App\FlowAction\FlowAction;
use Laser\Core\Framework\App\Lifecycle\AbstractAppLoader;
use Laser\Core\Framework\Context;
use Laser\Core\Framework\DataAbstractionLayer\EntityRepository;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Uuid\Uuid;

/**
 * @internal
 */
#[Package('core')]
class FlowActionPersister
{
    public function __construct(
        private readonly EntityRepository $flowActionsRepository,
        private readonly AbstractAppLoader $appLoader,
        private readonly Connection $connection
    ) {
    }

    public function updateActions(FlowAction $flowAction, string $appId, Context $context, string $defaultLocale): void
    {
        $existingFlowActions = $this->connection->fetchAllKeyValue('SELECT name, LOWER(HEX(id)) FROM app_flow_action WHERE app_id = :appId', [
            'appId' => Uuid::fromHexToBytes($appId),
        ]);

        $flowActions = $flowAction->getActions() ? $flowAction->getActions()->getActions() : [];
        $upserts = [];

        foreach ($flowActions as $action) {
            $payload = array_merge([
                'appId' => $appId,
                'iconRaw' => $this->appLoader->getFlowActionIcon($action->getMeta()->getIcon(), $flowAction),
            ], $action->toArray($defaultLocale));

            $existing = $existingFlowActions[$action->getMeta()->getName()] ?? null;
            if ($existing) {
                $payload['id'] = $existing;
                unset($existingFlowActions[$action->getMeta()->getName()]);
            }

            $upserts[] = $payload;
        }

        if (!empty($upserts)) {
            $this->flowActionsRepository->upsert($upserts, $context);
        }

        $this->deleteOldAppFlowActions($existingFlowActions, $context);
    }

    private function deleteOldAppFlowActions(array $toBeRemoved, Context $context): void
    {
        $ids = array_values($toBeRemoved);

        if (empty($ids)) {
            return;
        }

        $ids = array_map(static fn (string $id): array => ['id' => $id], array_values($ids));

        $this->flowActionsRepository->delete($ids, $context);
    }
}
