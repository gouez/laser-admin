<?php declare(strict_types=1);

namespace Laser\Core\System\SalesChannel;

use Laser\Core\Checkout\Cart\CartException;
use Laser\Core\Checkout\Cart\Delivery\Struct\ShippingLocation;
use Laser\Core\Checkout\Cart\Tax\Struct\TaxRule;
use Laser\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Laser\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Laser\Core\Checkout\Customer\CustomerEntity;
use Laser\Core\Checkout\Payment\PaymentMethodEntity;
use Laser\Core\Checkout\Shipping\ShippingMethodEntity;
use Laser\Core\Framework\Context;
use Laser\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Struct\StateAwareTrait;
use Laser\Core\Framework\Struct\Struct;
use Laser\Core\System\Currency\CurrencyEntity;
use Laser\Core\System\SalesChannel\Exception\ContextPermissionsLockedException;
use Laser\Core\System\Tax\Exception\TaxNotFoundException;
use Laser\Core\System\Tax\TaxCollection;

#[Package('core')]
class SalesChannelContext extends Struct
{
    use StateAwareTrait;

    /**
     * Unique token for context, e.g. stored in session or provided in request headers
     *
     * @var string
     */
    protected $token;

    /**
     * @var CustomerGroupEntity
     */
    protected $currentCustomerGroup;

    /**
     * @var CurrencyEntity
     */
    protected $currency;

    /**
     * @var SalesChannelEntity
     */
    protected $salesChannel;

    /**
     * @var TaxCollection
     */
    protected $taxRules;

    /**
     * @var CustomerEntity|null
     */
    protected $customer;

    /**
     * @var PaymentMethodEntity
     */
    protected $paymentMethod;

    /**
     * @var ShippingMethodEntity
     */
    protected $shippingMethod;

    /**
     * @var ShippingLocation
     */
    protected $shippingLocation;

    /**
     * @var mixed[]
     */
    protected $permissions = [];

    /**
     * @var bool
     */
    protected $permisionsLocked = false;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @internal
     *
     * @param array<string, string[]> $areaRuleIds
     */
    public function __construct(
        Context $baseContext,
        string $token,
        private ?string $domainId,
        SalesChannelEntity $salesChannel,
        CurrencyEntity $currency,
        CustomerGroupEntity $currentCustomerGroup,
        TaxCollection $taxRules,
        PaymentMethodEntity $paymentMethod,
        ShippingMethodEntity $shippingMethod,
        ShippingLocation $shippingLocation,
        ?CustomerEntity $customer,
        private CashRoundingConfig $itemRounding,
        private CashRoundingConfig $totalRounding,
        protected array $areaRuleIds = []
    ) {
        $this->currentCustomerGroup = $currentCustomerGroup;
        $this->currency = $currency;
        $this->salesChannel = $salesChannel;
        $this->taxRules = $taxRules;
        $this->customer = $customer;
        $this->paymentMethod = $paymentMethod;
        $this->shippingMethod = $shippingMethod;
        $this->shippingLocation = $shippingLocation;
        $this->token = $token;
        $this->context = $baseContext;
    }

    public function getCurrentCustomerGroup(): CustomerGroupEntity
    {
        return $this->currentCustomerGroup;
    }

    public function getCurrency(): CurrencyEntity
    {
        return $this->currency;
    }

    public function getSalesChannel(): SalesChannelEntity
    {
        return $this->salesChannel;
    }

    public function getTaxRules(): TaxCollection
    {
        return $this->taxRules;
    }

    /**
     * Get the tax rules depend on the customer billing address
     * respectively the shippingLocation if there is no customer
     */
    public function buildTaxRules(string $taxId): TaxRuleCollection
    {
        $tax = $this->taxRules->get($taxId);

        if ($tax === null || $tax->getRules() === null) {
            throw new TaxNotFoundException($taxId);
        }

        if ($tax->getRules()->first() !== null) {
            // NEXT-21735 - This is covered randomly
            // @codeCoverageIgnoreStart
            return new TaxRuleCollection([
                new TaxRule($tax->getRules()->first()->getTaxRate(), 100),
            ]);
            // @codeCoverageIgnoreEnd
        }

        return new TaxRuleCollection([
            new TaxRule($tax->getTaxRate(), 100),
        ]);
    }

    public function getCustomer(): ?CustomerEntity
    {
        return $this->customer;
    }

    public function getPaymentMethod(): PaymentMethodEntity
    {
        return $this->paymentMethod;
    }

    public function getShippingMethod(): ShippingMethodEntity
    {
        return $this->shippingMethod;
    }

    public function getShippingLocation(): ShippingLocation
    {
        return $this->shippingLocation;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    /**
     * @return string[]
     */
    public function getRuleIds(): array
    {
        return $this->getContext()->getRuleIds();
    }

    /**
     * @param array<string> $ruleIds
     */
    public function setRuleIds(array $ruleIds): void
    {
        $this->getContext()->setRuleIds($ruleIds);
    }

    /**
     * @internal
     *
     * @return array<string, string[]>
     */
    public function getAreaRuleIds(): array
    {
        return $this->areaRuleIds;
    }

    /**
     * @internal
     *
     * @param string[] $areas
     *
     * @return string[]
     */
    public function getRuleIdsByAreas(array $areas): array
    {
        $ruleIds = [];

        foreach ($areas as $area) {
            if (empty($this->areaRuleIds[$area])) {
                continue;
            }

            $ruleIds = array_unique(array_merge($ruleIds, $this->areaRuleIds[$area]));
        }

        return array_values($ruleIds);
    }

    /**
     * @internal
     *
     * @param array<string, string[]> $areaRuleIds
     */
    public function setAreaRuleIds(array $areaRuleIds): void
    {
        $this->areaRuleIds = $areaRuleIds;
    }

    public function lockRules(): void
    {
        $this->getContext()->lockRules();
    }

    public function lockPermissions(): void
    {
        $this->permisionsLocked = true;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getTaxState(): string
    {
        return $this->context->getTaxState();
    }

    public function setTaxState(string $taxState): void
    {
        $this->context->setTaxState($taxState);
    }

    public function getTaxCalculationType(): string
    {
        return $this->getSalesChannel()->getTaxCalculationType();
    }

    /**
     * @return mixed[]
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * @param mixed[] $permissions
     */
    public function setPermissions(array $permissions): void
    {
        if ($this->permisionsLocked) {
            throw new ContextPermissionsLockedException();
        }

        $this->permissions = array_filter($permissions);
    }

    public function getApiAlias(): string
    {
        return 'sales_channel_context';
    }

    public function hasPermission(string $permission): bool
    {
        return \array_key_exists($permission, $this->permissions) && (bool) $this->permissions[$permission];
    }

    public function getSalesChannelId(): string
    {
        return $this->getSalesChannel()->getId();
    }

    public function addState(string ...$states): void
    {
        $this->context->addState(...$states);
    }

    public function removeState(string $state): void
    {
        $this->context->removeState($state);
    }

    public function hasState(string ...$states): bool
    {
        return $this->context->hasState(...$states);
    }

    /**
     * @return string[]
     */
    public function getStates(): array
    {
        return $this->context->getStates();
    }

    public function getDomainId(): ?string
    {
        return $this->domainId;
    }

    public function setDomainId(?string $domainId): void
    {
        $this->domainId = $domainId;
    }

    /**
     * @return string[]
     */
    public function getLanguageIdChain(): array
    {
        return $this->context->getLanguageIdChain();
    }

    public function getLanguageId(): string
    {
        return $this->context->getLanguageId();
    }

    public function getVersionId(): string
    {
        return $this->context->getVersionId();
    }

    public function considerInheritance(): bool
    {
        return $this->context->considerInheritance();
    }

    public function getTotalRounding(): CashRoundingConfig
    {
        return $this->totalRounding;
    }

    public function setTotalRounding(CashRoundingConfig $totalRounding): void
    {
        $this->totalRounding = $totalRounding;
    }

    public function getItemRounding(): CashRoundingConfig
    {
        return $this->itemRounding;
    }

    public function setItemRounding(CashRoundingConfig $itemRounding): void
    {
        $this->itemRounding = $itemRounding;
    }

    public function getCurrencyId(): string
    {
        return $this->getCurrency()->getId();
    }

    public function ensureLoggedIn(bool $allowGuest = true): void
    {
        if ($this->customer === null) {
            throw CartException::customerNotLoggedIn();
        }

        if (!$allowGuest && $this->customer->getGuest()) {
            throw CartException::customerNotLoggedIn();
        }
    }

    public function getCustomerId(): ?string
    {
        return $this->customer ? $this->customer->getId() : null;
    }
}
