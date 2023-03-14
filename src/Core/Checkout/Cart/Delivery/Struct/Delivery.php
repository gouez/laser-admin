<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Cart\Delivery\Struct;

use Laser\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Laser\Core\Checkout\Shipping\ShippingMethodEntity;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Struct\Struct;

#[Package('checkout')]
class Delivery extends Struct
{
    /**
     * @var DeliveryPositionCollection
     */
    protected $positions;

    /**
     * @var ShippingLocation
     */
    protected $location;

    /**
     * @var DeliveryDate
     */
    protected $deliveryDate;

    /**
     * @var ShippingMethodEntity
     */
    protected $shippingMethod;

    /**
     * @var CalculatedPrice
     */
    protected $shippingCosts;

    public function __construct(
        DeliveryPositionCollection $positions,
        DeliveryDate $deliveryDate,
        ShippingMethodEntity $shippingMethod,
        ShippingLocation $location,
        CalculatedPrice $shippingCosts
    ) {
        $this->location = $location;
        $this->positions = $positions;
        $this->deliveryDate = $deliveryDate;
        $this->shippingMethod = $shippingMethod;
        $this->shippingCosts = $shippingCosts;
    }

    public function getPositions(): DeliveryPositionCollection
    {
        return $this->positions;
    }

    public function getLocation(): ShippingLocation
    {
        return $this->location;
    }

    public function getDeliveryDate(): DeliveryDate
    {
        return $this->deliveryDate;
    }

    public function getShippingMethod(): ShippingMethodEntity
    {
        return $this->shippingMethod;
    }

    public function setShippingMethod(ShippingMethodEntity $shippingMethod): void
    {
        $this->shippingMethod = $shippingMethod;
    }

    public function getShippingCosts(): CalculatedPrice
    {
        return $this->shippingCosts;
    }

    public function setShippingCosts(CalculatedPrice $shippingCosts): void
    {
        $this->shippingCosts = $shippingCosts;
    }

    public function getApiAlias(): string
    {
        return 'cart_delivery';
    }
}
