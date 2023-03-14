<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Test\Cart\Facade;

use Laser\Core\Checkout\Cart\Cart;
use Laser\Core\Checkout\Cart\Hook\CartAware;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Script\Execution\Awareness\SalesChannelContextAwareTrait;
use Laser\Core\Framework\Script\Execution\Hook;
use Laser\Core\Framework\Test\IdsCollection;
use Laser\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 */
#[Package('checkout')]
class CartTestHook extends Hook implements CartAware
{
    use SalesChannelContextAwareTrait;

    public IdsCollection $ids;

    private static array $serviceIds;

    /**
     * @param array<string> $serviceIds
     */
    public function __construct(
        private readonly string $name,
        private readonly Cart $cart,
        SalesChannelContext $context,
        array $data = [],
        array $serviceIds = []
    ) {
        parent::__construct($context->getContext());
        $this->salesChannelContext = $context;
        self::$serviceIds = $serviceIds;

        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    public function getCart(): Cart
    {
        return $this->cart;
    }

    public static function getServiceIds(): array
    {
        return self::$serviceIds;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
