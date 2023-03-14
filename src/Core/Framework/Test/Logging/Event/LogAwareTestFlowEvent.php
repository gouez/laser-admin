<?php declare(strict_types=1);

namespace Laser\Core\Framework\Test\Logging\Event;

use Monolog\Level;
use Laser\Core\Content\Test\Flow\TestFlowBusinessEvent;
use Laser\Core\Framework\Log\LogAware;

/**
 * @internal
 */
class LogAwareTestFlowEvent extends TestFlowBusinessEvent implements LogAware
{
    final public const EVENT_NAME = 'test.flow_event.log_aware';

    public function getName(): string
    {
        return self::EVENT_NAME;
    }

    public function getLogData(): array
    {
        return ['awesomekey' => 'awesomevalue'];
    }

    /**
     * @deprecated tag:v6.6.0 - reason:return-type-change - Return type will change to @see \Monolog\Level
     */
    public function getLogLevel(): int
    {
        return Level::Emergency->value;
    }
}
