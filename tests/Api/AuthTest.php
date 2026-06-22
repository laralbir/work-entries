<?php

declare(strict_types=1);

namespace App\Tests\Api;

class AuthTest extends ApiTestCase
{
    protected function setUp(): void
    {
        $this->truncateTables();
    }

    public function testLoginSucceeds(): void
    {
        $this->createUser('alice@example.com', 'password123', 'Alice');

        $response = static::createClient()->request('POST', '/api/login_check', [
            'json' => ['email' => 'alice@example.com', 'password' => 'password123'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('token', $response->toArray());
    }

    public function testLoginFailsWithWrongPassword(): void
    {
        $this->createUser('bob@example.com', 'correctpassword', 'Bob');

        static::createClient()->request('POST', '/api/login_check', [
            'json' => ['email' => 'bob@example.com', 'password' => 'wrongpassword'],
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testLoginFailsWithUnknownEmail(): void
    {
        static::createClient()->request('POST', '/api/login_check', [
            'json' => ['email' => 'nobody@example.com', 'password' => 'password123'],
        ]);

        $this->assertResponseStatusCodeSame(401);
    }
}
