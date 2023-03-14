<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Cart\Delivery\Struct;

use Laser\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Struct\Struct;
use Laser\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Laser\Core\System\Country\CountryEntity;

#[Package('checkout')]
class ShippingLocation extends Struct
{
    /**
     * @var CountryEntity
     */
    protected $country;

    /**
     * @var CountryStateEntity|null
     */
    protected $state;

    /**
     * @var CustomerAddressEntity|null
     */
    protected $address;

    public function __construct(
        CountryEntity $country,
        ?CountryStateEntity $state,
        ?CustomerAddressEntity $address
    ) {
        $this->country = $country;
        $this->state = $state;
        $this->address = $address;
    }

    public static function createFromAddress(CustomerAddressEntity $address): self
    {
        return new self(
            $address->getCountry(),
            $address->getCountryState(),
            $address
        );
    }

    public static function createFromCountry(CountryEntity $country): self
    {
        return new self($country, null, null);
    }

    public function getCountry(): CountryEntity
    {
        if ($this->address) {
            return $this->address->getCountry();
        }

        return $this->country;
    }

    public function getState(): ?CountryStateEntity
    {
        if ($this->address) {
            return $this->address->getCountryState();
        }

        return $this->state;
    }

    public function getAddress(): ?CustomerAddressEntity
    {
        return $this->address;
    }

    public function getApiAlias(): string
    {
        return 'cart_delivery_shipping_location';
    }
}
