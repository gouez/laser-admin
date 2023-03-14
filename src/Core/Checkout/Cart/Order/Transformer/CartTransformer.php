<?php declare(strict_types=1);

namespace Laser\Core\Checkout\Cart\Order\Transformer;

use Laser\Core\Checkout\Cart\Cart;
use Laser\Core\Defaults;
use Laser\Core\Framework\DataAbstractionLayer\FieldSerializer\JsonFieldSerializer;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Util\Random;
use Laser\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
class CartTransformer
{
    public static function transform(Cart $cart, SalesChannelContext $context, string $stateId, bool $setOrderDate = true): array
    {
        $currency = $context->getCurrency();

        $data = [
            'price' => $cart->getPrice(),
            'shippingCosts' => $cart->getShippingCosts(),
            'stateId' => $stateId,
            'currencyId' => $currency->getId(),
            'currencyFactor' => $currency->getFactor(),
            'salesChannelId' => $context->getSalesChannel()->getId(),
            'lineItems' => [],
            'deliveries' => [],
            'deepLinkCode' => Random::getBase64UrlString(32),
            'customerComment' => $cart->getCustomerComment(),
            'affiliateCode' => $cart->getAffiliateCode(),
            'campaignCode' => $cart->getCampaignCode(),
        ];

        if ($setOrderDate) {
            $data['orderDateTime'] = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);
        }

        $data['itemRounding'] = json_decode(JsonFieldSerializer::encodeJson($context->getItemRounding()), true, 512, \JSON_THROW_ON_ERROR);
        $data['totalRounding'] = json_decode(JsonFieldSerializer::encodeJson($context->getTotalRounding()), true, 512, \JSON_THROW_ON_ERROR);

        return $data;
    }
}
