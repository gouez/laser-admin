<?php declare(strict_types=1);

namespace Laser\Core\Content\MailTemplate\Service\Event;

use Monolog\Level;
use Laser\Core\Content\Flow\Dispatching\Aware\ContentsAware;
use Laser\Core\Content\Flow\Dispatching\Aware\RecipientsAware;
use Laser\Core\Content\Flow\Dispatching\Aware\SubjectAware;
use Laser\Core\Framework\Context;
use Laser\Core\Framework\Event\EventData\ArrayType;
use Laser\Core\Framework\Event\EventData\EventDataCollection;
use Laser\Core\Framework\Event\EventData\ScalarValueType;
use Laser\Core\Framework\Log\LogAware;
use Laser\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('sales-channel')]
class MailSentEvent extends Event implements LogAware, SubjectAware, ContentsAware, RecipientsAware
{
    final public const EVENT_NAME = 'mail.sent';

    /**
     * @param array<string, mixed> $recipients
     * @param array<string, mixed> $contents
     */
    public function __construct(
        private readonly string $subject,
        private readonly array $recipients,
        private readonly array $contents,
        private readonly Context $context,
        private readonly ?string $eventName = null
    ) {
    }

    public static function getAvailableData(): EventDataCollection
    {
        return (new EventDataCollection())
            ->add('subject', new ScalarValueType(ScalarValueType::TYPE_STRING))
            ->add('contents', new ScalarValueType(ScalarValueType::TYPE_STRING))
            ->add('recipients', new ArrayType(new ScalarValueType(ScalarValueType::TYPE_STRING)));
    }

    public function getName(): string
    {
        return self::EVENT_NAME;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContents(): array
    {
        return $this->contents;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRecipients(): array
    {
        return $this->recipients;
    }

    public function getLogData(): array
    {
        return [
            'eventName' => $this->eventName,
            'subject' => $this->subject,
            'recipients' => $this->recipients,
            'contents' => $this->contents,
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
