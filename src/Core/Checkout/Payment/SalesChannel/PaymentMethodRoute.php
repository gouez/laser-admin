<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Payment\SalesChannel;

use Laser\Core\Checkout\Payment\PaymentMethodCollection;
use Laser\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Laser\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Laser\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Plugin\Exception\DecorationPatternException;
use Laser\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Laser\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('checkout')]
class PaymentMethodRoute extends AbstractPaymentMethodRoute
{
    /**
     * @internal
     */
    public function __construct(private readonly SalesChannelRepository $paymentMethodsRepository)
    {
    }

    public function getDecorated(): AbstractPaymentMethodRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(path: '/store-api/payment-method', name: 'store-api.payment.method', methods: ['GET', 'POST'], defaults: ['_entity' => 'payment_method'])]
    public function load(Request $request, SalesChannelContext $context, Criteria $criteria): PaymentMethodRouteResponse
    {
        $criteria
            ->addFilter(new EqualsFilter('active', true))
            ->addSorting(new FieldSorting('position'))
            ->addAssociation('media');

        $result = $this->paymentMethodsRepository->search($criteria, $context);

        /** @var PaymentMethodCollection $paymentMethods */
        $paymentMethods = $result->getEntities();

        if ($request->query->getBoolean('onlyAvailable') || $request->request->getBoolean('onlyAvailable')) {
            $paymentMethods = $paymentMethods->filterByActiveRules($context);
        }

        $result->assign(['entities' => $paymentMethods, 'elements' => $paymentMethods, 'total' => $paymentMethods->count()]);

        return new PaymentMethodRouteResponse($result);
    }
}
