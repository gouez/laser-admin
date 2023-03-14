<?php declare(strict_types=1);

namespace Laser\Core\Framework\Webhook\Subscriber;

use Laser\Core\Framework\Context;
use Laser\Core\Framework\DataAbstractionLayer\EntityRepository;
use Laser\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Webhook\EventLog\WebhookEventLogDefinition;
use Laser\Core\Framework\Webhook\Message\WebhookEventMessage;
use Laser\Core\Framework\Webhook\WebhookEntity;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;

/**
 * @internal
 */
#[Package('core')]
class RetryWebhookMessageFailedSubscriber implements EventSubscriberInterface
{
    private const MAX_WEBHOOK_ERROR_COUNT = 10;

    /**
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $webhookRepository,
        private readonly EntityRepository $webhookEventLogRepository
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageFailedEvent::class => 'failed',
        ];
    }

    public function failed(WorkerMessageFailedEvent $event): void
    {
        if ($event->willRetry()) {
            return;
        }

        $message = $event->getEnvelope()->getMessage();
        if (!$message instanceof WebhookEventMessage) {
            return;
        }

        $webhookId = $message->getWebhookId();
        $webhookEventLogId = $message->getWebhookEventId();

        $this->markWebhookEventFailed($webhookEventLogId);

        /** @var WebhookEntity|null $webhook */
        $webhook = $this->webhookRepository
            ->search(new Criteria([$webhookId]), Context::createDefaultContext())
            ->get($webhookId);

        if ($webhook === null || !$webhook->isActive()) {
            return;
        }

        $webhookErrorCount = $webhook->getErrorCount() + 1;
        $params = [
            'id' => $webhook->getId(),
            'errorCount' => $webhookErrorCount,
        ];

        if ($webhookErrorCount >= self::MAX_WEBHOOK_ERROR_COUNT) {
            $params = array_merge($params, [
                'errorCount' => 0,
                'active' => false,
            ]);
        }

        $this->webhookRepository->update([$params], Context::createDefaultContext());
    }

    private function markWebhookEventFailed(string $id): void
    {
        $this->webhookEventLogRepository->update([
            ['id' => $id, 'deliveryStatus' => WebhookEventLogDefinition::STATUS_FAILED],
        ], Context::createDefaultContext());
    }
}