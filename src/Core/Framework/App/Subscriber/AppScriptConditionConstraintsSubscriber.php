<?php declare(strict_types=1);

namespace Laser\Core\Framework\App\Subscriber;

use Laser\Core\Framework\App\Aggregate\AppScriptCondition\AppScriptConditionEntity;
use Laser\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Laser\Core\Framework\Log\Package;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('core')]
class AppScriptConditionConstraintsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'app_script_condition.loaded' => 'unserialize',
        ];
    }

    public function unserialize(EntityLoadedEvent $event): void
    {
        /** @var AppScriptConditionEntity $entity */
        foreach ($event->getEntities() as $entity) {
            $constraints = $entity->getConstraints();
            if ($constraints === null || !\is_string($constraints)) {
                continue;
            }

            $entity->setConstraints(unserialize($constraints));
        }
    }
}
