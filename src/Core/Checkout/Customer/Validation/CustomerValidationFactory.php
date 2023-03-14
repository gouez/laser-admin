<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Customer\Validation;

use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Validation\DataValidationDefinition;
use Laser\Core\Framework\Validation\DataValidationFactoryInterface;
use Laser\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

#[Package('customer-order')]
class CustomerValidationFactory implements DataValidationFactoryInterface
{
    /**
     * @internal
     */
    public function __construct(
        /**
         * @todo seems to be the usecase for the laser api - import or so. maybe rename to CustomerImportValidationService
         */
        private readonly DataValidationFactoryInterface $profileValidation
    ) {
    }

    public function create(SalesChannelContext $context): DataValidationDefinition
    {
        $definition = new DataValidationDefinition('customer.create');

        $profileDefinition = $this->profileValidation->create($context);

        $this->merge($definition, $profileDefinition);

        $this->addConstraints($definition);

        return $definition;
    }

    public function update(SalesChannelContext $context): DataValidationDefinition
    {
        $definition = new DataValidationDefinition('customer.update');

        $profileDefinition = $this->profileValidation->update($context);

        $this->merge($definition, $profileDefinition);

        $this->addConstraints($definition);

        return $definition;
    }

    private function addConstraints(DataValidationDefinition $definition): void
    {
        $definition
            ->add('email', new NotBlank(), new Email())
            ->add('active', new Type('boolean'));
    }

    /**
     * merges constraints from the second definition into the first validation definition
     */
    private function merge(DataValidationDefinition $definition, DataValidationDefinition $profileDefinition): void
    {
        foreach ($profileDefinition->getProperties() as $key => $constraints) {
            $definition->add($key, ...$constraints);
        }
    }
}
