<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Promotion\Cart\Discount\Filter;

use Laser\Core\Checkout\Promotion\Cart\Discount\DiscountLineItem;
use Laser\Core\Checkout\Promotion\Cart\Discount\DiscountPackageCollection;
use Laser\Core\Framework\Log\Package;

#[Package('checkout')]
class AdvancedPackagePicker
{
    /**
     * @internal
     */
    public function __construct(private readonly FilterServiceRegistry $registry)
    {
    }

    public function pickItems(DiscountLineItem $discount, DiscountPackageCollection $scopePackages): DiscountPackageCollection
    {
        $pickerKey = $discount->getFilterPickerKey();

        // we start by modifying our packages
        // with the currently set picker, if available
        // this restructures our packages
        if (!empty($pickerKey)) {
            $picker = $this->registry->getPicker($pickerKey);

            // get the new list of packages to consider
            $scopePackages = $picker->pickItems($scopePackages);
        }

        return $scopePackages;
    }
}
