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

    // -------------------------------------------------------------------------
    // POST /api/auth/revoke
    // -------------------------------------------------------------------------

    public function testRevokeTokenRequiresAuth(): void
    {
        static::createClient()->request('POST', '/api/auth/revoke');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testRevokeTokenReturns204(): void
    {
        $this->createUser('alice@example.com', 'password123', 'Alice');
        $token = $this->getToken('alice@example.com', 'password123');

        static::createClient()->request('POST', '/api/auth/revoke', $this->authHeaders($token));

        $this->assertResponseStatusCodeSame(204);
    }

    public function testRevokedTokenIsRejectedOnSubsequentRequests(): void
    {
        $this->createUser('alice@example.com', 'password123', 'Alice');
        $token = $this->getToken('alice@example.com', 'password123');

        static::createClient()->request('POST', '/api/auth/revoke', $this->authHeaders($token));
        $this->assertResponseStatusCodeSame(204);

        static::createClient()->request('GET', '/api/users', $this->authHeaders($token));
        $this->assertResponseStatusCodeSame(401);
    }

    public function testNewTokenWorksAfterRevokingOld(): void
    {
        $this->createUser('alice@example.com', 'password123', 'Alice');
        $token = $this->getToken('alice@example.com', 'password123');

        static::createClient()->request('POST', '/api/auth/revoke', $this->authHeaders($token));
        $this->assertResponseStatusCodeSame(204);

        $newToken = $this->getToken('alice@example.com', 'password123');
        static::createClient()->request('GET', '/api/users', $this->authHeaders($newToken));
        $this->assertResponseIsSuccessful();
    }

    public function testMultipleTokensAreRevokedIndependently(): void
    {
        $this->createUser('alice@example.com', 'password123', 'Alice');

        $token1 = $this->getToken('alice@example.com', 'password123');
        $token2 = $this->getToken('alice@example.com', 'password123');

        // Revoke only the first token
        static::createClient()->request('POST', '/api/auth/revoke', $this->authHeaders($token1));
        $this->assertResponseStatusCodeSame(204);

        // First token rejected
        static::createClient()->request('GET', '/api/users', $this->authHeaders($token1));
        $this->assertResponseStatusCodeSame(401);

        // Second token still valid (has a different jti)
        static::createClient()->request('GET', '/api/users', $this->authHeaders($token2));
        $this->assertResponseIsSuccessful();
    }
}
