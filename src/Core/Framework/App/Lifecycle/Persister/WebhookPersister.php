<?php declare(strict_types=1);

namespace Laser\Core\Framework\App\Lifecycle\Persister;

use Laser\Core\Framework\App\Manifest\Manifest;
use Laser\Core\Framework\Context;
use Laser\Core\Framework\DataAbstractionLayer\EntityRepository;
use Laser\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Laser\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Webhook\WebhookCollection;
use Laser\Core\Framework\Webhook\WebhookEntity;

/**
 * @internal only for use by the app-system, will be considered internal from v6.4.0 onward
 */
#[Package('core')]
class WebhookPersister
{
    public function __construct(private readonly EntityRepository $webhookRepository)
    {
    }

    public function updateWebhooks(Manifest $manifest, string $appId, string $defaultLocale, Context $context): void
    {
        $existingWebhooks = $this->getExistingWebhooks($appId, $context);

        $webhooks = $manifest->getWebhooks() ? $manifest->getWebhooks()->getWebhooks() : [];
        $upserts = [];

        foreach ($webhooks as $webhook) {
            $payload = $webhook->toArray($defaultLocale);
            $payload['appId'] = $appId;
            $payload['eventName'] = $webhook->getEvent();

            /** @var WebhookEntity|null $existing */
            $existing = $existingWebhooks->filterByProperty('name', $webhook->getName())->first();
            if ($existing) {
                $payload['id'] = $existing->getId();
                $existingWebhooks->remove($existing->getId());
            }

            $upserts[] = $payload;
        }

        if (!empty($upserts)) {
            $this->webhookRepository->upsert($upserts, $context);
        }

        $this->deleteOldWebhooks($existingWebhooks, $context);
    }

    public function updateWebhooksFromArray(array $webhooks, string $appId, Context $context): void
    {
        $existingWebhooks = $this->getExistingWebhooks($appId, $context);
        $upserts = [];

        foreach ($webhooks as $webhook) {
            /** @var WebhookEntity|null $existing */
            $existing = $existingWebhooks->filterByProperty('name', $webhook['name'])->first();

            if ($existing) {
                $webhook['id'] = $existing->getId();
                $existingWebhooks->remove($existing->getId());
            }

            $upserts[] = $webhook;
        }

        if (!empty($upserts)) {
            $this->webhookRepository->upsert($upserts, $context);
        }

        $this->deleteOldWebhooks($existingWebhooks, $context);
    }

    private function deleteOldWebhooks(WebhookCollection $toBeRemoved, Context $context): void
    {
        /** @var array<string> $ids */
        $ids = $toBeRemoved->getIds();

        if (empty($ids)) {
            return;
        }

        $ids = array_map(static fn (string $id): array => ['id' => $id], array_values($ids));

        $this->webhookRepository->delete($ids, $context);
    }

    private function getExistingWebhooks(string $appId, Context $context): WebhookCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('appId', $appId));

        /** @var WebhookCollection $webhooks */
        $webhooks = $this->webhookRepository->search($criteria, $context)->getEntities();

        return $webhooks;
    }
}
