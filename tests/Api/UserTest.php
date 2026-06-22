<?php

declare(strict_types=1);

namespace App\Tests\Api;

class UserTest extends ApiTestCase
{
    protected function setUp(): void
    {
        $this->truncateTables();
    }

    // -------------------------------------------------------------------------
    // Registration (public)
    // -------------------------------------------------------------------------

    public function testRegisterUser(): void
    {
        $response = static::createClient()->request('POST', '/api/users', [
            'json' => [
                'name'     => 'Alice',
                'email'    => 'alice@example.com',
                'password' => 'password123',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertArrayHasKey('id', $data);
        $this->assertSame('Alice', $data['name']);
        $this->assertSame('alice@example.com', $data['email']);
        $this->assertArrayNotHasKey('password', $data);
        $this->assertArrayNotHasKey('plainPassword', $data);
    }

    public function testRegisterUserValidationFailsOnBlankName(): void
    {
        static::createClient()->request('POST', '/api/users', [
            'json' => ['name' => '', 'email' => 'x@x.com', 'password' => 'password123'],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testRegisterUserValidationFailsOnBlankPassword(): void
    {
        static::createClient()->request('POST', '/api/users', [
            'json' => ['name' => 'Alice', 'email' => 'alice@example.com', 'password' => ''],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testRegisterUserValidationFailsOnShortPassword(): void
    {
        static::createClient()->request('POST', '/api/users', [
            'json' => ['name' => 'Alice', 'email' => 'alice@example.com', 'password' => 'short'],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testRegisterUserValidationFailsOnDuplicateEmail(): void
    {
        $payload = ['name' => 'Alice', 'email' => 'alice@example.com', 'password' => 'password123'];

        static::createClient()->request('POST', '/api/users', ['json' => $payload]);
        $this->assertResponseStatusCodeSame(201);

        static::createClient()->request('POST', '/api/users', ['json' => $payload]);
        $this->assertResponseStatusCodeSame(422);
    }

    // -------------------------------------------------------------------------
    // GET collection
    // -------------------------------------------------------------------------

    public function testGetUserCollectionRequiresAuth(): void
    {
        static::createClient()->request('GET', '/api/users');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetUserCollection(): void
    {
        $this->createUser('alice@example.com', 'password123', 'Alice');
        $token = $this->getToken('alice@example.com', 'password123');

        $response = static::createClient()->request('GET', '/api/users', $this->authHeaders($token));

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertCount(1, $data['member']);
    }

    // -------------------------------------------------------------------------
    // GET item
    // -------------------------------------------------------------------------

    public function testGetOwnUser(): void
    {
        $user  = $this->createUser('alice@example.com', 'password123', 'Alice');
        $token = $this->getToken('alice@example.com', 'password123');

        $response = static::createClient()->request(
            'GET',
            '/api/users/' . $user->getId()->toRfc4122(),
            $this->authHeaders($token),
        );

        $this->assertResponseIsSuccessful();
        $this->assertSame('Alice', $response->toArray()['name']);
    }

    // -------------------------------------------------------------------------
    // PATCH (update own profile)
    // -------------------------------------------------------------------------

    public function testUpdateOwnUser(): void
    {
        $user  = $this->createUser('alice@example.com', 'password123', 'Alice');
        $token = $this->getToken('alice@example.com', 'password123');

        $response = static::createClient()->request(
            'PATCH',
            '/api/users/' . $user->getId()->toRfc4122(),
            [
                'json'    => ['name' => 'Alice Updated'],
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/merge-patch+json',
                ],
            ],
        );

        $this->assertResponseIsSuccessful();
        $this->assertSame('Alice Updated', $response->toArray()['name']);
    }

    public function testUpdateOtherUserForbidden(): void
    {
        $this->createUser('alice@example.com', 'password123', 'Alice');
        $bob   = $this->createUser('bob@example.com', 'password123', 'Bob');
        $token = $this->getToken('alice@example.com', 'password123');

        static::createClient()->request(
            'PATCH',
            '/api/users/' . $bob->getId()->toRfc4122(),
            [
                'json'    => ['name' => 'Hacked'],
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/merge-patch+json',
                ],
            ],
        );

        $this->assertResponseStatusCodeSame(403);
    }

    // -------------------------------------------------------------------------
    // DELETE (soft delete own profile)
    // -------------------------------------------------------------------------

    public function testDeleteOwnUser(): void
    {
        $user  = $this->createUser('alice@example.com', 'password123', 'Alice');
        $token = $this->getToken('alice@example.com', 'password123');
        $id    = $user->getId()->toRfc4122();

        static::createClient()->request('DELETE', '/api/users/' . $id, $this->authHeaders($token));
        $this->assertResponseStatusCodeSame(204);

        static::createClient()->request('GET', '/api/users/' . $id, $this->authHeaders($token));
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteOtherUserForbidden(): void
    {
        $this->createUser('alice@example.com', 'password123', 'Alice');
        $bob   = $this->createUser('bob@example.com', 'password123', 'Bob');
        $token = $this->getToken('alice@example.com', 'password123');

        static::createClient()->request(
            'DELETE',
            '/api/users/' . $bob->getId()->toRfc4122(),
            $this->authHeaders($token),
        );

        $this->assertResponseStatusCodeSame(403);
    }
}
