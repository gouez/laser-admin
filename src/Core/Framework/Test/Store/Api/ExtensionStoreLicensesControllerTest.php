<?php declare(strict_types=1);

namespace Laser\Core\Framework\Test\Store\Api;

use PHPUnit\Framework\TestCase;
use Laser\Core\Framework\Context;
use Laser\Core\Framework\Store\Api\ExtensionStoreLicensesController;
use Laser\Core\Framework\Store\Services\ExtensionStoreLicensesService;
use Laser\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
class ExtensionStoreLicensesControllerTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testCancelSubscription(): void
    {
        $provider = $this->createMock(ExtensionStoreLicensesService::class);
        $provider->method('cancelSubscription');

        $controller = new ExtensionStoreLicensesController(
            $provider
        );

        $response = $controller->cancelSubscription(1, Context::createDefaultContext());
        static::assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testRateLicensedExtension(): void
    {
        $provider = $this->createMock(ExtensionStoreLicensesService::class);

        $controller = new ExtensionStoreLicensesController(
            $provider
        );

        $request = new Request();
        $request->request->replace([
            'authorName' => 'Max',
            'headline' => 'Max',
            'rating' => 1,
            'version' => '2.0.0',
        ]);

        $response = $controller->rateLicensedExtension(1, $request, Context::createDefaultContext());
        static::assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }
}
