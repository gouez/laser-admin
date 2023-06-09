<?php declare(strict_types=1);

namespace Laser\Core\Framework\App\Lifecycle\Registration;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Laser\Core\Framework\Log\Package;

/**
 * @internal only for use by the app-system
 */
#[Package('core')]
class PrivateHandshake implements AppHandshakeInterface
{
    public function __construct(
        private readonly string $shopUrl,
        private readonly string $secret,
        private readonly string $appEndpoint,
        private readonly string $appName,
        private readonly string $shopId,
        private readonly string $laserVersion
    ) {
    }

    public function assembleRequest(): RequestInterface
    {
        $date = new \DateTime();
        $uri = new Uri($this->appEndpoint);

        $uri = Uri::withQueryValues($uri, [
            'shop-id' => $this->shopId,
            'shop-url' => $this->shopUrl,
            'timestamp' => (string) $date->getTimestamp(),
        ]);

        $signature = hash_hmac('sha256', $uri->getQuery(), $this->secret);

        return new Request(
            'GET',
            $uri,
            [
                'laser-app-signature' => $signature,
                'sw-version' => $this->laserVersion,
            ]
        );
    }

    public function fetchAppProof(): string
    {
        return hash_hmac('sha256', $this->shopId . $this->shopUrl . $this->appName, $this->secret);
    }
}
