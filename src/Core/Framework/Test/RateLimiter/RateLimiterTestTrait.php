<?php declare(strict_types=1);

namespace Laser\Core\Framework\Test\RateLimiter;

use PHPUnit\Framework\TestCase;
use Laser\Core\Framework\RateLimiter\RateLimiter;
use Laser\Core\Framework\RateLimiter\RateLimiterFactory;
use Laser\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimit;

trait RateLimiterTestTrait
{
    use IntegrationTestBehaviour;

    /**
     * @param array<string, int> $factories
     */
    private function mockResetLimiter(array $factories, TestCase $phpUnit): RateLimiter
    {
        $rateLimiter = new RateLimiter();

        foreach ($factories as $factory => $expects) {
            $limiter = $phpUnit->createMock(LimiterInterface::class);
            $limiter->method('consume')->willReturn(new RateLimit(1, new \DateTimeImmutable(), true, 1));
            $limiter->expects($phpUnit::exactly($expects))->method('reset');

            $limiterFactory = $phpUnit->createMock(RateLimiterFactory::class);
            $limiterFactory->method('create')->willReturn($limiter);

            $rateLimiter->registerLimiterFactory($factory, $limiterFactory);
        }

        return $rateLimiter;
    }

    private function clearCache(): void
    {
        $this->getContainer()->get('cache.rate_limiter')->clear();
    }
}
