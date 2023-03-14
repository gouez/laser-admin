<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Cart\Order\Transformer;

use Laser\Core\Checkout\Customer\CustomerCollection;
use Laser\Core\Checkout\Customer\CustomerEntity;
use Laser\Core\Framework\Log\Package;

#[Package('checkout')]
class CustomerTransformer
{
    public static function transformCollection(CustomerCollection $customers, bool $useIdAsKey = false): array
    {
        $output = [];
        foreach ($customers as $customer) {
            $output[$customer->getId()] = self::transform($customer);
        }

        if (!$useIdAsKey) {
            $output = array_values($output);
        }

        return $output;
    }

    public static function transform(CustomerEntity $customer): array
    {
        return [
            'customerId' => $customer->getId(),
            'email' => $customer->getEmail(),
            'firstName' => $customer->getFirstName(),
            'lastName' => $customer->getLastName(),
            'salutationId' => $customer->getSalutationId(),
            'title' => $customer->getTitle(),
            'vatIds' => $customer->getVatIds(),
            'company' => $customer->getCompany(),
            'customerNumber' => $customer->getCustomerNumber(),
            'remoteAddress' => $customer->getRemoteAddress(),
            'customFields' => $customer->getCustomFields(),
        ];
    }
}
