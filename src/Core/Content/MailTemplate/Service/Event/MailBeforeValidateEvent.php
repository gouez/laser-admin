<?php declare(strict_types=1);

namespace Laser\Core\Content\MailTemplate\Service\Event;

use Monolog\Level;
use Laser\Core\Content\Flow\Dispatching\Aware\DataAware;
use Laser\Core\Content\Flow\Dispatching\Aware\TemplateDataAware;
use Laser\Core\Framework\Context;
use Laser\Core\Framework\Event\EventData\ArrayType;
use Laser\Core\Framework\Event\EventData\EventDataCollection;
use Laser\Core\Framework\Event\EventData\ScalarValueType;
use Laser\Core\Framework\Log\LogAware;
use Laser\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('sales-channel')]
class MailBeforeValidateEvent extends Event implements LogAware, TemplateDataAware, DataAware
{
    final public const EVENT_NAME = 'mail.before.send';

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $templateData
     */
    public function __construct(
        private array $data,
        private readonly Context $context,
        private array $templateData = []
    ) {
    }

    public static function getAvailableData(): EventDataCollection
    {
        return (new EventDataCollection())
            ->add('data', new ArrayType(new ScalarValueType(ScalarValueType::TYPE_STRING)))
            ->add('templateData', new ArrayType(new ScalarValueType(ScalarValueType::TYPE_STRING)));
    }

    public function getName(): string
    {
        return self::EVENT_NAME;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @param float|int|string|array<mixed>|object $value
     */
    public function addData(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    /**
     * @return array<string, mixed>
     */
    public function getTemplateData(): array
    {
        return $this->templateData;
    }

    /**
     * @param array<string, mixed> $templateData
     */
    public function setTemplateData(array $templateData): void
    {
        $this->templateData = $templateData;
    }

    /**
     * @param float|int|string|array<mixed>|object $value
     */
    public function addTemplateData(string $key, $value): void
    {
        $this->templateData[$key] = $value;
    }

    public function getLogData(): array
    {
        $data = $this->data;
        unset($data['binAttachments']);

        return [
            'data' => $data,
            'eventName' => $this->templateData['eventName'] ?? null,
            'templateData' => $this->templateData,
        ];
    }

    /**
     * @deprecated tag:v6.6.0 - reason:return-type-change - Return type will change to @see \Monolog\Level
     */
    public function getLogLevel(): int
    {
        return Level::Info->value;
    }
}