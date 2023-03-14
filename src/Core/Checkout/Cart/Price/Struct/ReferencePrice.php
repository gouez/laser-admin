<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Cart\Price\Struct;

use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Util\FloatComparator;

#[Package('checkout')]
class ReferencePrice extends ReferencePriceDefinition
{
    /**
     * @var float
     */
    protected $price;

    public function __construct(
        float $price,
        float $purchaseUnit,
        float $referenceUnit,
        string $unitName
    ) {
        parent::__construct($purchaseUnit, $referenceUnit, $unitName);

        $this->price = FloatComparator::cast($price);
    }

    public function getPrice(): float
    {
        return FloatComparator::cast($this->price);
    }

    public function getApiAlias(): string
    {
        return 'cart_price_reference';
    }
}