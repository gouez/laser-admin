<?php declare(strict_types=1);

namespace Laser\Core\Content\ImportExport\DataAbstractionLayer;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Laser\Core\Content\ImportExport\Exception\DeleteDefaultProfileException;
use Laser\Core\Content\ImportExport\ImportExportProfileDefinition;
use Laser\Core\Framework\DataAbstractionLayer\Write\Command\DeleteCommand;
use Laser\Core\Framework\DataAbstractionLayer\Write\Validation\PreWriteValidationEvent;
use Laser\Core\Framework\Log\Package;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('system-settings')]
class SystemDefaultValidator implements EventSubscriberInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PreWriteValidationEvent::class => 'preValidate',
        ];
    }

    /**
     * @internal
     *
     * @throws DeleteDefaultProfileException
     */
    public function preValidate(PreWriteValidationEvent $event): void
    {
        $ids = [];
        $writeCommands = $event->getCommands();

        foreach ($writeCommands as $command) {
            if ($command->getDefinition()->getClass() === ImportExportProfileDefinition::class
                && $command instanceof DeleteCommand
            ) {
                $ids[] = $command->getPrimaryKey()['id'];
            }
        }

        $filteredIds = $this->filterSystemDefaults($ids);
        if (!empty($filteredIds)) {
            $event->getExceptions()->add(new DeleteDefaultProfileException($filteredIds));
        }
    }

    private function filterSystemDefaults(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $result = $this->connection->executeQuery(
            'SELECT id FROM import_export_profile WHERE id IN (:idList) AND system_default = 1',
            ['idList' => $ids],
            ['idList' => ArrayParameterType::STRING]
        );

        return $result->fetchFirstColumn();
    }
}
