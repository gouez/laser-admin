<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Customer\SalesChannel;

use Laser\Core\Checkout\Cart\SalesChannel\CartService;
use Laser\Core\Checkout\Customer\CustomerEntity;
use Laser\Core\Checkout\Customer\Event\CustomerLogoutEvent;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Plugin\Exception\DecorationPatternException;
use Laser\Core\Framework\Util\Random;
use Laser\Core\Framework\Validation\DataBag\RequestDataBag;
use Laser\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Laser\Core\System\SalesChannel\ContextTokenResponse;
use Laser\Core\System\SalesChannel\SalesChannelContext;
use Laser\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('customer-order')]
class LogoutRoute extends AbstractLogoutRoute
{
    /**
     * @internal
     */
    public function __construct(
        private readonly SalesChannelContextPersister $contextPersister,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly SystemConfigService $systemConfig,
        private readonly CartService $cartService
    ) {
    }

    public function getDecorated(): AbstractLogoutRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(path: '/store-api/account/logout', name: 'store-api.account.logout', methods: ['POST'], defaults: ['_loginRequired' => true, '_loginRequiredAllowGuest' => true])]
    public function logout(SalesChannelContext $context, RequestDataBag $data): ContextTokenResponse
    {
        /** @var CustomerEntity $customer */
        $customer = $context->getCustomer();
        if ($this->shouldDelete($context)) {
            $this->cartService->deleteCart($context);
            $this->contextPersister->delete($context->getToken(), $context->getSalesChannelId());

            $event = new CustomerLogoutEvent($context, $customer);
            $this->eventDispatcher->dispatch($event);

            return new ContextTokenResponse($context->getToken());
        }

        $newToken = Random::getAlphanumericString(32);
        if ((bool) $data->get('replace-token')) {
            $newToken = $this->contextPersister->replace($context->getToken(), $context);
        }

        $context->assign([
            'token' => $newToken,
        ]);

        $event = new CustomerLogoutEvent($context, $customer);
        $this->eventDispatcher->dispatch($event);

        return new ContextTokenResponse($context->getToken());
    }

    private function shouldDelete(SalesChannelContext $context): bool
    {
        $config = $this->systemConfig->get('core.loginRegistration.invalidateSessionOnLogOut', $context->getSalesChannelId());

        if ($config) {
            return true;
        }

        if ($context->getCustomer() === null) {
            return true;
        }

        return $context->getCustomer()->getGuest();
    }
}
