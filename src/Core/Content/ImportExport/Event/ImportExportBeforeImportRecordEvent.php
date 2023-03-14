<?php declare(strict_types=1);

namespace Laser\Core\Content\ImportExport\Event;

use Laser\Core\Content\ImportExport\Struct\Config;
use Laser\Core\Framework\Context;
use Laser\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('system-settings')]
class ImportExportBeforeImportRecordEvent extends Event
{
    public function __construct(
        private array $record,
        private readonly array $row,
        private readonly Config $config,
        private readonly Context $context
    ) {
    }

    public function getRecord(): array
    {
        return $this->record;
    }

    public function setRecord(array $record): void
    {
        $this->record = $record;
    }

    public function getRow(): array
    {
        return $this->row;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
