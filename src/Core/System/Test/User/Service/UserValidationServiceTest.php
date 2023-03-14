<?php declare(strict_types=1);

namespace Laser\Core\System\Test\User\Service;

use PHPUnit\Framework\TestCase;
use Laser\Core\Framework\Context;
use Laser\Core\Framework\DataAbstractionLayer\EntityRepository;
use Laser\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Laser\Core\Framework\Log\Package;
use Laser\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Laser\Core\Framework\Uuid\Uuid;
use Laser\Core\System\User\Service\UserValidationService;

/**
 * @internal
 */
#[Package('system-settings')]
class UserValidationServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    private EntityRepository $userRepository;

    private EntityRepository $localeRepository;

    private UserValidationService $userValidationService;

    public function setUp(): void
    {
        $this->userRepository = $this->getContainer()->get('user.repository');
        $this->localeRepository = $this->getContainer()->get('locale.repository');
        $this->userValidationService = $this->getContainer()->get(UserValidationService::class);
    }

    public function testIfReturnsTrueForUniqueEmails(): void
    {
        $userId = Uuid::randomHex();

        $context = Context::createDefaultContext();
        $localeIds = $this->localeRepository->searchIds(new Criteria(), $context)->getIds();
        $firstLocale = array_pop($localeIds);

        $this->userRepository->create([
            [
                'id' => $userId,
                'username' => 'some User',
                'firstName' => 'first',
                'lastName' => 'last',
                'localeId' => $firstLocale,
                'email' => 'user@laser.com',
                'password' => 'laser',
            ],
        ], $context);

        $userIdToTest = Uuid::randomHex();
        static::assertTrue($this->userValidationService->checkEmailUnique('some@other.email', $userIdToTest, $context));
        static::assertTrue($this->userValidationService->checkEmailUnique('user@laser.com', $userId, $context));
    }

    public function testIfReturnsFalseForDuplicateEmails(): void
    {
        $userId = Uuid::randomHex();

        $context = Context::createDefaultContext();
        $localeIds = $this->localeRepository->searchIds(new Criteria(), $context)->getIds();

        $firstLocale = array_pop($localeIds);

        $this->userRepository->create([
            [
                'id' => $userId,
                'username' => 'some User',
                'firstName' => 'first',
                'lastName' => 'last',
                'localeId' => $firstLocale,
                'email' => 'user@laser.com',
                'password' => 'laser',
            ],
        ], $context);

        $userIdToTest = Uuid::randomHex();
        static::assertFalse($this->userValidationService->checkEmailUnique('user@laser.com', $userIdToTest, $context));
    }

    public function testIfReturnsTrueForUniqueUsernames(): void
    {
        $userId = Uuid::randomHex();

        $context = Context::createDefaultContext();
        $localeIds = $this->localeRepository->searchIds(new Criteria(), $context)->getIds();
        $firstLocale = array_pop($localeIds);

        $this->userRepository->create([
            [
                'id' => $userId,
                'username' => 'some User',
                'firstName' => 'first',
                'lastName' => 'last',
                'localeId' => $firstLocale,
                'email' => 'user@laser.com',
                'password' => 'laser',
            ],
        ], $context);

        $userIdToTest = Uuid::randomHex();
        static::assertTrue($this->userValidationService->checkUsernameUnique('other User', $userIdToTest, $context));
        static::assertTrue($this->userValidationService->checkUsernameUnique('some User', $userId, $context));
    }

    public function testIfReturnsFalseForDuplicateUsernames(): void
    {
        $userId = Uuid::randomHex();

        $context = Context::createDefaultContext();
        $localeIds = $this->localeRepository->searchIds(new Criteria(), $context)->getIds();
        $firstLocale = array_pop($localeIds);

        $this->userRepository->create([
            [
                'id' => $userId,
                'username' => 'some User',
                'firstName' => 'first',
                'lastName' => 'last',
                'localeId' => $firstLocale,
                'email' => 'user@laser.com',
                'password' => 'laser',
            ],
        ], $context);

        $userIdToTest = Uuid::randomHex();
        static::assertFalse($this->userValidationService->checkUsernameUnique('some User', $userIdToTest, $context));
    }
}
