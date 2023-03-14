<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Customer\Event;

use Laser\Core\Checkout\Customer\CustomerDefinition;
use Laser\Core\Checkout\Customer\CustomerEntity;
use Laser\Core\Content\Flow\Dispatching\Aware\ConfirmUrlAware;
use Laser\Core\Framework\Context;
use Laser\Core\Framework\Event\CustomerAware;
use Laser\Core\Framework\Event\EventData\EntityType;
use Laser\Core\Framework\Event\EventData\EventDataCollection;
use Laser\Core\Framework\Event\EventData\MailRecipientStruct;
use Laser\Core\Framework\Event\EventData\ScalarValueType;
use Laser\Core\Framework\Event\MailAware;
use Laser\Core\Framework\Event\SalesChannelAware;
use Laser\Core\Framework\Log\Package;
use Laser\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('customer-order')]
class DoubleOptInGuestOrderEvent extends Event implements SalesChannelAware, CustomerAware, MailAware, ConfirmUrlAware
{
    public const EVENT_NAME = 'checkout.customer.double_opt_in_guest_order';

    /**
     * @var CustomerEntity
     */
    private $customer;

    /**
     * @var SalesChannelContext
     */
    private $salesChannelContext;

    /**
     * @var string
     */
    private $confirmUrl;

    /**
     * @var MailRecipientStruct
     */
    private $mailRecipientStruct;

    public function __construct(
        CustomerEntity $customer,
        SalesChannelContext $salesChannelContext,
        string $confirmUrl
    ) {
        $this->customer = $customer;
        $this->salesChannelContext = $salesChannelContext;
        $this->confirmUrl = $confirmUrl;
    }

    public static function getAvailableData(): EventDataCollection
    {
        return (new EventDataCollection())
            ->add('customer', new EntityType(CustomerDefinition::class))
            ->add('confirmUrl', new ScalarValueType(ScalarValueType::TYPE_STRING));
    }

    public function getName(): string
    {
        return self::EVENT_NAME;
    }

    public function getMailStruct(): MailRecipientStruct
    {
        if (!$this->mailRecipientStruct instanceof MailRecipientStruct) {
            $this->mailRecipientStruct = new MailRecipientStruct([
                $this->customer->getEmail() => $this->customer->getFirstName() . ' ' . $this->customer->getLastName(),
            ]);
        }

        return $this->mailRecipientStruct;
    }

    public function getCustomer(): CustomerEntity
    {
        return $this->customer;
    }

    public function getConfirmUrl(): string
    {
        return $this->confirmUrl;
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannelContext->getSalesChannel()->getId();
    }

    public function getContext(): Context
    {
        return $this->salesChannelContext->getContext();
    }

    public function getCustomerId(): string
    {
        return $this->getCustomer()->getId();
    }
}