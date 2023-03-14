<?php declare(strict_types=1);

namespace Laser\Core\Content\Rule\DataAbstractionLayer;

use Doctrine\DBAL\Connection;
use Laser\Core\Checkout\Cart\CartRuleLoader;
use Laser\Core\Content\Rule\Event\RuleIndexerEvent;
use Laser\Core\Content\Rule\RuleDefinition;
use Laser\Core\Content\Rule\RuleEvents;
use Laser\Core\Framework\DataAbstractionLayer\Dbal\Common\IteratorFactory;
use Laser\Core\Framework\DataAbstractionLayer\Doctrine\RetryableQuery;
use Laser\Core\Framework\DataAbstractionLayer\EntityRepository;
use Laser\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Laser\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Laser\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexer;
use Laser\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexingMessage;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Plugin\Event\PluginPostActivateEvent;
use Laser\Core\Framework\Plugin\Event\PluginPostDeactivateEvent;
use Laser\Core\Framework\Plugin\Event\PluginPostInstallEvent;
use Laser\Core\Framework\Plugin\Event\PluginPostUninstallEvent;
use Laser\Core\Framework\Plugin\Event\PluginPostUpdateEvent;
use Laser\Core\Framework\Plugin\Exception\DecorationPatternException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('business-ops')]
class RuleIndexer extends EntityIndexer implements EventSubscriberInterface
{
    final public const PAYLOAD_UPDATER = 'rule.payload';

    final public const AREA_UPDATER = 'rule.area';

    /**
     * @internal
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly IteratorFactory $iteratorFactory,
        private readonly EntityRepository $repository,
        private readonly RulePayloadUpdater $payloadUpdater,
        private readonly RuleAreaUpdater $areaUpdater,
        private readonly CartRuleLoader $cartRuleLoader,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function getName(): string
    {
        return 'rule.indexer';
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PluginPostInstallEvent::class => 'refreshPlugin',
            PluginPostActivateEvent::class => 'refreshPlugin',
            PluginPostUpdateEvent::class => 'refreshPlugin',
            PluginPostDeactivateEvent::class => 'refreshPlugin',
            PluginPostUninstallEvent::class => 'refreshPlugin',
            RuleEvents::RULE_WRITTEN_EVENT => 'onRuleWritten',
        ];
    }

    public function refreshPlugin(): void
    {
        // Delete the payload and invalid flag of all rules
        $update = new RetryableQuery(
            $this->connection,
            $this->connection->prepare('UPDATE `rule` SET `payload` = null, `invalid` = 0')
        );
        $update->execute();
    }

    public function iterate(?array $offset): ?EntityIndexingMessage
    {
        $iterator = $this->iteratorFactory->createIterator($this->repository->getDefinition(), $offset);

        $ids = $iterator->fetch();

        if (empty($ids)) {
            return null;
        }

        return new RuleIndexingMessage(array_values($ids), $iterator->getOffset());
    }

    public function update(EntityWrittenContainerEvent $event): ?EntityIndexingMessage
    {
        $updates = $event->getPrimaryKeys(RuleDefinition::ENTITY_NAME);

        if (empty($updates)) {
            return null;
        }

        $this->handle(new RuleIndexingMessage(array_values($updates), null, $event->getContext()));

        return null;
    }

    public function handle(EntityIndexingMessage $message): void
    {
        $ids = $message->getData();

        $ids = array_unique(array_filter($ids));
        if (empty($ids)) {
            return;
        }

        if ($message->allow(self::PAYLOAD_UPDATER)) {
            $this->payloadUpdater->update($ids);
        }

        if ($message->allow(self::AREA_UPDATER)) {
            $this->areaUpdater->update($ids);
        }

        $this->eventDispatcher->dispatch(new RuleIndexerEvent($ids, $message->getContext(), $message->getSkip()));
    }

    public function getTotal(): int
    {
        return $this->iteratorFactory->createIterator($this->repository->getDefinition())->fetchCount();
    }

    public function getDecorated(): EntityIndexer
    {
        throw new DecorationPatternException(static::class);
    }

    public function onRuleWritten(EntityWrittenEvent $event): void
    {
        $this->cartRuleLoader->invalidate();
    }
}