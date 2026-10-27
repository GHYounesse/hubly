<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * End-to-end checks of the REST API. They need a database with the migrations applied and the JWT
 * key pair (php bin/console lexik:jwt:generate-keypair); CI does both.
 */
class RestApiTest extends ApiTestCase
{
    private const PASSWORD = 'Api-Test-1234';

    protected static ?bool $alwaysBootKernel = false;

    public function testTheApiRequiresAToken(): void
    {
        static::createClient()->request('GET', '/api/projects');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testLoginReturnsAToken(): void
    {
        $client = static::createClient();
        $this->createUser('login@example.test', ['ROLE_USER']);

        $response = $client->request('POST', '/api/login', ['json' => ['email' => 'login@example.test', 'password' => self::PASSWORD]]);

        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('token', $response->toArray());
    }

    public function testWrongPasswordIsRejected(): void
    {
        $client = static::createClient();
        $this->createUser('wrong@example.test', ['ROLE_USER']);

        $client->request('POST', '/api/login', ['json' => ['email' => 'wrong@example.test', 'password' => 'nope']]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testAUserCanReadProjectsButNotWrite(): void
    {
        $client = static::createClient();
        $token = $this->tokenFor($client, 'reader@example.test', ['ROLE_USER']);

        $collection = $client->request('GET', '/api/projects', ['auth_bearer' => $token])->toArray();
        $this->assertResponseIsSuccessful();
        // "member" with API Platform 4 defaults, "hydra:member" when the Hydra prefix is enabled
        $this->assertTrue(isset($collection['member']) || isset($collection['hydra:member']), 'a JSON-LD collection is returned');

        $client->request('POST', '/api/hostings', [
            'auth_bearer' => $token,
            'json' => ['site' => 'example.test'],
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAUserWithoutCompaniesSeesNothing(): void
    {
        $client = static::createClient();
        $token = $this->tokenFor($client, 'nobody@example.test', ['ROLE_USER']);

        $collection = $client->request('GET', '/api/companies', ['auth_bearer' => $token])->toArray();

        $this->assertSame([], $collection['member'] ?? $collection['hydra:member'] ?? null, 'no company is assigned to this user');
    }

    public function testAnAdminCanCreateAHostingAndItsCredentialsStayPrivate(): void
    {
        $client = static::createClient();
        $token = $this->tokenFor($client, 'admin-api@example.test', ['ROLE_ADMIN']);

        $client->request('POST', '/api/hostings', [
            'auth_bearer' => $token,
            'json' => ['site' => 'api-created.example.test', 'fournisseur' => 'OVH', 'estActif' => true],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['site' => 'api-created.example.test']);
        $this->assertArrayNotHasKey('acces', $client->getResponse()->toArray());
    }

    public function testInvalidDataIsRejectedWithAViolationList(): void
    {
        $client = static::createClient();
        $token = $this->tokenFor($client, 'validator@example.test', ['ROLE_ADMIN']);

        $client->request('POST', '/api/hostings', ['auth_bearer' => $token, 'json' => ['site' => '']]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testTheOpenApiDocumentationIsPublicAndDescribesTheLogin(): void
    {
        $response = static::createClient()->request('GET', '/api/docs', ['headers' => ['Accept' => 'application/vnd.openapi+json']]);

        $this->assertResponseIsSuccessful();
        $paths = $response->toArray()['paths'];
        $this->assertArrayHasKey('/api/login', $paths);
        $this->assertArrayHasKey('/api/projects', $paths);
        $this->assertArrayHasKey('/api/companies', $paths);
        $this->assertArrayHasKey('/api/hostings', $paths);
    }

    /** @param string[] $roles */
    private function tokenFor($client, string $email, array $roles): string
    {
        $this->createUser($email, $roles);
        $response = $client->request('POST', '/api/login', ['json' => ['email' => $email, 'password' => self::PASSWORD]]);

        return $response->toArray()['token'];
    }

    /** @param string[] $roles */
    private function createUser(string $email, array $roles): void
    {
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        if ($em->getRepository(User::class)->findOneBy(['email' => $email])) {
            return;
        }

        $user = (new User())->setEmail($email)->setRoles($roles)->setStatut('actif')->setNom('Api')->setPrenom('Test');
        $user->setPassword($container->get(UserPasswordHasherInterface::class)->hashPassword($user, self::PASSWORD));
        $user->setCreated(new \DateTime())->setUpdated(new \DateTime());

        $em->persist($user);
        $em->flush();
    }
}
