<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Order\SalesChannel;

use Laser\Core\Checkout\Cart\CartBehavior;
use Laser\Core\Checkout\Cart\CartRuleLoader;
use Laser\Core\Checkout\Cart\Order\OrderConverter;
use Laser\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Laser\Core\Checkout\Customer\CustomerEntity;
use Laser\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Laser\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Laser\Core\Checkout\Order\Event\OrderPaymentMethodChangedCriteriaEvent;
use Laser\Core\Checkout\Order\Event\OrderPaymentMethodChangedEvent;
use Laser\Core\Checkout\Order\Exception\PaymentMethodNotChangeableException;
use Laser\Core\Checkout\Order\OrderEntity;
use Laser\Core\Checkout\Payment\Exception\InvalidTransactionException;
use Laser\Core\Checkout\Payment\Exception\UnknownPaymentMethodException;
use Laser\Core\Checkout\Payment\SalesChannel\AbstractPaymentMethodRoute;
use Laser\Core\Framework\Context;
use Laser\Core\Framework\DataAbstractionLayer\EntityRepository;
use Laser\Core\Framework\DataAbstractionLayer\Exception\EntityNotFoundException;
use Laser\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Laser\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Laser\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Plugin\Exception\DecorationPatternException;
use Laser\Core\Framework\Uuid\Uuid;
use Laser\Core\System\SalesChannel\Context\SalesChannelContextService;
use Laser\Core\System\SalesChannel\SalesChannelContext;
use Laser\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Laser\Core\System\StateMachine\Exception\IllegalTransitionException;
use Laser\Core\System\StateMachine\Loader\InitialStateIdLoader;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('customer-order')]
class SetPaymentOrderRoute extends AbstractSetPaymentOrderRoute
{
    /**
     * @internal
     */
    public function __construct(
        private readonly OrderService $orderService,
        private readonly EntityRepository $orderRepository,
        private readonly AbstractPaymentMethodRoute $paymentRoute,
        private readonly OrderConverter $orderConverter,
        private readonly CartRuleLoader $cartRuleLoader,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly InitialStateIdLoader $initialStateIdLoader
    ) {
    }

    public function getDecorated(): AbstractSetPaymentOrderRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(path: '/store-api/order/payment', name: 'store-api.order.set-payment', methods: ['POST'], defaults: ['_loginRequired' => true, '_loginRequiredAllowGuest' => true])]
    public function setPayment(Request $request, SalesChannelContext $context): SetPaymentOrderRouteResponse
    {
        $paymentMethodId = (string) $request->request->get('paymentMethodId');

        $orderId = (string) $request->request->get('orderId');
        $order = $this->loadOrder($orderId, $context);

        $context = $this->orderConverter->assembleSalesChannelContext(
            $order,
            $context->getContext(),
            [SalesChannelContextService::PAYMENT_METHOD_ID => $paymentMethodId]
        );

        $this->validateRequest($context, $paymentMethodId);

        $this->validatePaymentState($order);

        $this->setPaymentMethod($paymentMethodId, $order, $context);

        return new SetPaymentOrderRouteResponse();
    }

    private function setPaymentMethod(string $paymentMethodId, OrderEntity $order, SalesChannelContext $salesChannelContext): void
    {
        $context = $salesChannelContext->getContext();

        if ($this->tryTransition($order, $paymentMethodId, $context)) {
            return;
        }

        $initialState = $this->initialStateIdLoader->get(OrderTransactionStates::STATE_MACHINE);

        $transactionAmount = new CalculatedPrice(
            $order->getPrice()->getTotalPrice(),
            $order->getPrice()->getTotalPrice(),
            $order->getPrice()->getCalculatedTaxes(),
            $order->getPrice()->getTaxRules()
        );

        $transactionId = Uuid::randomHex();
        $payload = [
            'id' => $order->getId(),
            'transactions' => [
                [
                    'id' => $transactionId,
                    'paymentMethodId' => $paymentMethodId,
                    'stateId' => $initialState,
                    'amount' => $transactionAmount,
                ],
            ],
            'ruleIds' => $this->getOrderRules($order, $salesChannelContext),
        ];

        $context->scope(
            Context::SYSTEM_SCOPE,
            function () use ($payload, $context): void {
                $this->orderRepository->update([$payload], $context);
            }
        );

        $changedOrder = $this->loadOrder($order->getId(), $salesChannelContext);
        $transactions = $changedOrder->getTransactions();
        if ($transactions === null || ($transaction = $transactions->get($transactionId)) === null) {
            throw new InvalidTransactionException($transactionId);
        }

        $event = new OrderPaymentMethodChangedEvent(
            $changedOrder,
            $transaction,
            $context,
            $salesChannelContext->getSalesChannelId()
        );
        $this->eventDispatcher->dispatch($event);
    }

    private function validateRequest(SalesChannelContext $salesChannelContext, string $paymentMethodId): void
    {
        $paymentRequest = new Request();
        $paymentRequest->query->set('onlyAvailable', '1');

        $availablePayments = $this->paymentRoute->load($paymentRequest, $salesChannelContext, new Criteria());

        if ($availablePayments->getPaymentMethods()->get($paymentMethodId) === null) {
            throw new UnknownPaymentMethodException($paymentMethodId);
        }
    }

    private function tryTransition(OrderEntity $order, string $paymentMethodId, Context $context): bool
    {
        $transactions = $order->getTransactions();
        if ($transactions === null || $transactions->count() < 1) {
            return false;
        }

        /** @var OrderTransactionEntity $lastTransaction */
        $lastTransaction = $transactions->last();

        foreach ($transactions as $transaction) {
            if ($transaction->getPaymentMethodId() === $paymentMethodId && $lastTransaction->getId() === $transaction->getId()) {
                $initialState = $this->initialStateIdLoader->get(OrderTransactionStates::STATE_MACHINE);
                if ($transaction->getStateId() === $initialState) {
                    return true;
                }

                try {
                    $this->orderService->orderTransactionStateTransition(
                        $transaction->getId(),
                        StateMachineTransitionActions::ACTION_REOPEN,
                        new ParameterBag(),
                        $context
                    );

                    return true;
                } catch (IllegalTransitionException) {
                    // if we can't reopen the last transaction with a matching payment method
                    // we have to create a new transaction and cancel the previous one
                }
            }

            if ($transaction->getStateMachineState() !== null
                && ($transaction->getStateMachineState()->getTechnicalName() === OrderTransactionStates::STATE_CANCELLED
                    || $transaction->getStateMachineState()->getTechnicalName() === OrderTransactionStates::STATE_FAILED)
            ) {
                continue;
            }

            $context->scope(
                Context::SYSTEM_SCOPE,
                function () use ($transaction, $context): void {
                    $this->orderService->orderTransactionStateTransition(
                        $transaction->getId(),
                        StateMachineTransitionActions::ACTION_CANCEL,
                        new ParameterBag(),
                        $context
                    );
                }
            );
        }

        return false;
    }

    /**
     * @return string[]
     */
    private function getOrderRules(OrderEntity $order, SalesChannelContext $salesChannelContext): array
    {
        $convertedCart = $this->orderConverter->convertToCart($order, $salesChannelContext->getContext());
        $ruleIds = $this->cartRuleLoader->loadByCart(
            $salesChannelContext,
            $convertedCart,
            new CartBehavior($salesChannelContext->getPermissions())
        )->getMatchingRules()->getIds();

        return array_values($ruleIds);
    }

    private function loadOrder(string $orderId, SalesChannelContext $context): OrderEntity
    {
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('transactions');
        $criteria->getAssociation('transactions')->addSorting(new FieldSorting('createdAt'));

        /** @var CustomerEntity $customer */
        $customer = $context->getCustomer();

        $criteria->addFilter(
            new EqualsFilter(
                'order.orderCustomer.customerId',
                $customer->getId()
            )
        );
        $criteria->addAssociations(['lineItems', 'deliveries', 'orderCustomer', 'tags']);

        $this->eventDispatcher->dispatch(new OrderPaymentMethodChangedCriteriaEvent($orderId, $criteria, $context));

        /** @var OrderEntity|null $order */
        $order = $this->orderRepository->search($criteria, $context->getContext())->first();

        if ($order === null) {
            throw new EntityNotFoundException('order', $orderId);
        }

        return $order;
    }

    /**
     * @throws PaymentMethodNotChangeableException
     */
    private function validatePaymentState(OrderEntity $order): void
    {
        if ($this->orderService->isPaymentChangeableByTransactionState($order)) {
            return;
        }

        throw new PaymentMethodNotChangeableException($order->getId());
    }
}