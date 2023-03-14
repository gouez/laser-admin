<?php declare(strict_types=1);

namespace Laser\Core\System\User\Api;

use Laser\Core\Framework\Context;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Laser\Core\System\User\Service\UserValidationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
#[Package('system-settings')]
class UserValidationController extends AbstractController
{
    /**
     * @internal
     */
    public function __construct(private readonly UserValidationService $userValidationService)
    {
    }

    #[Route(path: 'api/_action/user/check-email-unique', name: 'api.action.check-email-unique', methods: ['POST'])]
    public function isEmailUnique(Request $request, Context $context): JsonResponse
    {
        if (!$request->request->has('email')) {
            throw new MissingRequestParameterException('email');
        }

        if (!$request->request->has('id')) {
            throw new MissingRequestParameterException('id');
        }

        $email = (string) $request->request->get('email');
        $id = (string) $request->request->get('id');

        return new JsonResponse(
            ['emailIsUnique' => $this->userValidationService->checkEmailUnique($email, $id, $context)]
        );
    }

    #[Route(path: 'api/_action/user/check-username-unique', name: 'api.action.check-username-unique', methods: ['POST'])]
    public function isUsernameUnique(Request $request, Context $context): JsonResponse
    {
        if (!$request->request->has('username')) {
            throw new MissingRequestParameterException('username');
        }

        if (!$request->request->has('id')) {
            throw new MissingRequestParameterException('id');
        }

        $username = (string) $request->request->get('username');
        $id = (string) $request->request->get('id');

        return new JsonResponse(
            ['usernameIsUnique' => $this->userValidationService->checkUsernameUnique($username, $id, $context)]
        );
    }
}
