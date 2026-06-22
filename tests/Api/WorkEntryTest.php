<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\WorkEntry;

class WorkEntryTest extends ApiTestCase
{
    protected function setUp(): void
    {
        $this->truncateTables();
    }

    // -------------------------------------------------------------------------
    // Clock-in
    // -------------------------------------------------------------------------

    public function testClockIn(): void
    {
        $this->createUser('alice@example.com', 'password123', 'Alice');
        $token = $this->getToken('alice@example.com', 'password123');

        $response = static::createClient()->request(
            'POST',
            '/api/work_entries/clock-in',
            $this->authHeaders($token),
        );

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('startDate', $data);
        $this->assertNull($data['endDate']);
    }

    // -------------------------------------------------------------------------
    // Clock-out
    // -------------------------------------------------------------------------

    public function testClockOutClosesEntry(): void
    {
        $this->createUser('alice@example.com', 'password123', 'Alice');
        $token  = $this->getToken('alice@example.com', 'password123');
        $client = static::createClient();

        $clockInResponse = $client->request('POST', '/api/work_entries/clock-in', $this->authHeaders($token));
        $id = $clockInResponse->toArray()['id'];

        $clockOutResponse = $client->request(
            'POST',
            '/api/work_entries/' . $id . '/clock-out',
            $this->authHeaders($token),
        );

        $this->assertResponseIsSuccessful();
        $this->assertNotNull($clockOutResponse->toArray()['endDate']);
    }

    public function testClockOutAlreadyClockedOut(): void
    {
        $this->createUser('alice@example.com', 'password123', 'Alice');
        $token  = $this->getToken('alice@example.com', 'password123');
        $client = static::createClient();

        $id = $client->request('POST', '/api/work_entries/clock-in', $this->authHeaders($token))->toArray()['id'];
        $client->request('POST', '/api/work_entries/' . $id . '/clock-out', $this->authHeaders($token));

        $client->request('POST', '/api/work_entries/' . $id . '/clock-out', $this->authHeaders($token));
        $this->assertResponseStatusCodeSame(422);
    }

    // -------------------------------------------------------------------------
    // GET collection (only own entries)
    // -------------------------------------------------------------------------

    public function testGetCollectionReturnsOnlyOwnEntries(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $bob   = $this->createUser('bob@example.com', 'password123', 'Bob');

        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('-2 hours')));
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('-1 hour')));
        $em->persist(new WorkEntry($bob, new \DateTimeImmutable()));
        $em->flush();

        $token    = $this->getToken('alice@example.com', 'password123');
        $response = static::createClient()->request('GET', '/api/work_entries', $this->authHeaders($token));

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertCount(2, $data['member']);
    }

    public function testGetCollectionRequiresAuth(): void
    {
        static::createClient()->request('GET', '/api/work_entries');
        $this->assertResponseStatusCodeSame(401);
    }

    // -------------------------------------------------------------------------
    // GET item
    // -------------------------------------------------------------------------

    public function testGetWorkEntryForbiddenForOtherUser(): void
    {
        $em  = $this->getEntityManager();
        $bob = $this->createUser('bob@example.com', 'password123', 'Bob');
        $this->createUser('alice@example.com', 'password123', 'Alice');

        $entry = new WorkEntry($bob, new \DateTimeImmutable());
        $em->persist($entry);
        $em->flush();
        $entryId = $entry->getId()->toRfc4122();

        $token = $this->getToken('alice@example.com', 'password123');

        static::createClient()->request(
            'GET',
            '/api/work_entries/' . $entryId,
            $this->authHeaders($token),
        );

        $this->assertResponseStatusCodeSame(403);
    }

    // -------------------------------------------------------------------------
    // POST (create with explicit dates)
    // -------------------------------------------------------------------------

    public function testCreateWorkEntry(): void
    {
        $this->createUser('alice@example.com', 'password123', 'Alice');
        $token = $this->getToken('alice@example.com', 'password123');

        $response = static::createClient()->request('POST', '/api/work_entries', [
            'json'    => ['startDate' => '2026-06-22T09:00:00+00:00'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertArrayHasKey('id', $response->toArray());
    }

    // -------------------------------------------------------------------------
    // PATCH (update)
    // -------------------------------------------------------------------------

    public function testUpdateOwnWorkEntry(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $entry = new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T09:00:00+00:00'));
        $em->persist($entry);
        $em->flush();

        $token = $this->getToken('alice@example.com', 'password123');

        $response = static::createClient()->request(
            'PATCH',
            '/api/work_entries/' . $entry->getId()->toRfc4122(),
            [
                'json'    => ['startDate' => '2026-06-22T10:00:00+00:00'],
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/merge-patch+json',
                ],
            ],
        );

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('10:00:00', $response->toArray()['startDate']);
    }

    public function testUpdateOtherUsersWorkEntryForbidden(): void
    {
        $em  = $this->getEntityManager();
        $bob = $this->createUser('bob@example.com', 'password123', 'Bob');
        $this->createUser('alice@example.com', 'password123', 'Alice');
        $entry = new WorkEntry($bob, new \DateTimeImmutable());
        $em->persist($entry);
        $em->flush();
        $entryId = $entry->getId()->toRfc4122();

        $token = $this->getToken('alice@example.com', 'password123');

        static::createClient()->request(
            'PATCH',
            '/api/work_entries/' . $entryId,
            [
                'json'    => ['startDate' => '2026-06-22T10:00:00+00:00'],
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/merge-patch+json',
                ],
            ],
        );

        $this->assertResponseStatusCodeSame(403);
    }

    // -------------------------------------------------------------------------
    // DELETE (soft delete)
    // -------------------------------------------------------------------------

    public function testDeleteOwnWorkEntry(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $entry = new WorkEntry($alice, new \DateTimeImmutable());
        $em->persist($entry);
        $em->flush();

        $token = $this->getToken('alice@example.com', 'password123');
        $id    = $entry->getId()->toRfc4122();

        static::createClient()->request('DELETE', '/api/work_entries/' . $id, $this->authHeaders($token));
        $this->assertResponseStatusCodeSame(204);

        static::createClient()->request('GET', '/api/work_entries/' . $id, $this->authHeaders($token));
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteOtherUsersWorkEntryForbidden(): void
    {
        $em  = $this->getEntityManager();
        $bob = $this->createUser('bob@example.com', 'password123', 'Bob');
        $this->createUser('alice@example.com', 'password123', 'Alice');
        $entry = new WorkEntry($bob, new \DateTimeImmutable());
        $em->persist($entry);
        $em->flush();
        $entryId = $entry->getId()->toRfc4122();

        $token = $this->getToken('alice@example.com', 'password123');

        static::createClient()->request(
            'DELETE',
            '/api/work_entries/' . $entryId,
            $this->authHeaders($token),
        );

        $this->assertResponseStatusCodeSame(403);
    }
}
