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
            '/api/work-entries/clock-in',
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

        $clockInResponse = $client->request('POST', '/api/work-entries/clock-in', $this->authHeaders($token));
        $id = $clockInResponse->toArray()['id'];

        $clockOutResponse = $client->request(
            'POST',
            '/api/work-entries/' . $id . '/clock-out',
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

        $id = $client->request('POST', '/api/work-entries/clock-in', $this->authHeaders($token))->toArray()['id'];
        $client->request('POST', '/api/work-entries/' . $id . '/clock-out', $this->authHeaders($token));

        $client->request('POST', '/api/work-entries/' . $id . '/clock-out', $this->authHeaders($token));
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
        $response = static::createClient()->request('GET', '/api/work-entries', $this->authHeaders($token));

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertCount(2, $data['member']);
    }

    public function testGetCollectionRequiresAuth(): void
    {
        static::createClient()->request('GET', '/api/work-entries');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetCollectionIncludesTotalItems(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-10T08:00:00+00:00'), new \DateTimeImmutable('2026-06-10T17:00:00+00:00')));
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-11T08:00:00+00:00'), new \DateTimeImmutable('2026-06-11T17:00:00+00:00')));
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-12T08:00:00+00:00'), new \DateTimeImmutable('2026-06-12T17:00:00+00:00')));
        $em->flush();

        $token = $this->getToken('alice@example.com', 'password123');
        $data  = static::createClient()->request('GET', '/api/work-entries', $this->authHeaders($token))->toArray();

        $this->assertSame(3, $data['totalItems']);
        $this->assertCount(3, $data['member']);
    }

    public function testGetCollectionFilterByStartDate(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-08T08:00:00+00:00'), new \DateTimeImmutable('2026-06-08T17:00:00+00:00')));
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-10T08:00:00+00:00'), new \DateTimeImmutable('2026-06-10T17:00:00+00:00')));
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-12T08:00:00+00:00'), new \DateTimeImmutable('2026-06-12T17:00:00+00:00')));
        $em->flush();

        $token = $this->getToken('alice@example.com', 'password123');
        $data  = static::createClient()->request(
            'GET',
            '/api/work-entries?startDate=2026-06-09T00:00:00Z',
            $this->authHeaders($token),
        )->toArray();

        $this->assertSame(2, $data['totalItems']);
        $this->assertCount(2, $data['member']);
    }

    public function testGetCollectionFilterByEndDate(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-08T08:00:00+00:00'), new \DateTimeImmutable('2026-06-08T17:00:00+00:00')));
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-10T08:00:00+00:00'), new \DateTimeImmutable('2026-06-10T17:00:00+00:00')));
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-12T08:00:00+00:00'), new \DateTimeImmutable('2026-06-12T17:00:00+00:00')));
        $em->flush();

        $token = $this->getToken('alice@example.com', 'password123');
        $data  = static::createClient()->request(
            'GET',
            '/api/work-entries?endDate=2026-06-11T23:59:59Z',
            $this->authHeaders($token),
        )->toArray();

        $this->assertSame(2, $data['totalItems']);
        $this->assertCount(2, $data['member']);
    }

    public function testGetCollectionFilterByDateRange(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-08T08:00:00+00:00'), new \DateTimeImmutable('2026-06-08T17:00:00+00:00')));
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-10T08:00:00+00:00'), new \DateTimeImmutable('2026-06-10T17:00:00+00:00')));
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-12T08:00:00+00:00'), new \DateTimeImmutable('2026-06-12T17:00:00+00:00')));
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-14T08:00:00+00:00'), new \DateTimeImmutable('2026-06-14T17:00:00+00:00')));
        $em->flush();

        $token = $this->getToken('alice@example.com', 'password123');
        $data  = static::createClient()->request(
            'GET',
            '/api/work-entries?startDate=2026-06-09T00:00:00Z&endDate=2026-06-13T00:00:00Z',
            $this->authHeaders($token),
        )->toArray();

        $this->assertSame(2, $data['totalItems']);
        $this->assertCount(2, $data['member']);
    }

    public function testGetCollectionPagination(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        for ($i = 1; $i <= 5; ++$i) {
            $em->persist(new WorkEntry($alice, new \DateTimeImmutable("2026-06-0{$i}T08:00:00+00:00"), new \DateTimeImmutable("2026-06-0{$i}T17:00:00+00:00")));
        }
        $em->flush();

        $token = $this->getToken('alice@example.com', 'password123');
        $data  = static::createClient()->request(
            'GET',
            '/api/work-entries?page=2&itemsPerPage=2',
            $this->authHeaders($token),
        )->toArray();

        $this->assertSame(5, $data['totalItems']);
        $this->assertCount(2, $data['member']);
    }

    public function testGetCollectionInvalidDateReturns400(): void
    {
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $token = $this->getToken('alice@example.com', 'password123');

        static::createClient()->request(
            'GET',
            '/api/work-entries?startDate=not-a-date',
            $this->authHeaders($token),
        );

        $this->assertResponseStatusCodeSame(400);
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
            '/api/work-entries/' . $entryId,
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

        $response = static::createClient()->request('POST', '/api/work-entries', [
            'json'    => ['startDate' => '2026-06-22T09:00:00+00:00'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertArrayHasKey('id', $response->toArray());
    }

    // -------------------------------------------------------------------------
    // POST — overlap (expect 422 Unprocessable Entity)
    // -------------------------------------------------------------------------

    //
    //  existing:       09:00 ├────────────────────┤ 17:00
    //       new: 07:00 ├────────────┤ 12:00
    //                        ↑ overlap (new starts before existing, ends inside)
    //
    public function testOverlapPartialLeft(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T09:00:00+00:00'), new \DateTimeImmutable('2026-06-22T17:00:00+00:00')));
        $em->flush();

        $token    = $this->getToken('alice@example.com', 'password123');
        $response = static::createClient()->request('POST', '/api/work-entries', [
            'json'    => ['startDate' => '2026-06-22T07:00:00+00:00', 'endDate' => '2026-06-22T12:00:00+00:00'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('overlap', strtolower($response->toArray(throw: false)['detail'] ?? ''));
    }

    //
    //  existing: 09:00 ├────────────────────┤ 17:00
    //       new:              12:00 ├──────────────────────┤ 18:00
    //                               ↑ overlap (new starts inside existing, ends after)
    //
    public function testOverlapPartialRight(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T09:00:00+00:00'), new \DateTimeImmutable('2026-06-22T17:00:00+00:00')));
        $em->flush();

        $token    = $this->getToken('alice@example.com', 'password123');
        $response = static::createClient()->request('POST', '/api/work-entries', [
            'json'    => ['startDate' => '2026-06-22T12:00:00+00:00', 'endDate' => '2026-06-22T18:00:00+00:00'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('overlap', strtolower($response->toArray(throw: false)['detail'] ?? ''));
    }

    //
    //  existing: 09:00 ├────────────────────────────┤ 17:00
    //       new:              11:00 ├──────┤ 15:00
    //                               ↑ overlap (new completely inside existing)
    //
    public function testOverlapNewInsideExisting(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T09:00:00+00:00'), new \DateTimeImmutable('2026-06-22T17:00:00+00:00')));
        $em->flush();

        $token    = $this->getToken('alice@example.com', 'password123');
        $response = static::createClient()->request('POST', '/api/work-entries', [
            'json'    => ['startDate' => '2026-06-22T11:00:00+00:00', 'endDate' => '2026-06-22T15:00:00+00:00'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('overlap', strtolower($response->toArray(throw: false)['detail'] ?? ''));
    }

    //
    //  existing:       11:00 ├──────┤ 15:00
    //       new: 09:00 ├────────────────────────────┤ 17:00
    //                         ↑ overlap (existing completely inside new)
    //
    public function testOverlapExistingInsideNew(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T11:00:00+00:00'), new \DateTimeImmutable('2026-06-22T15:00:00+00:00')));
        $em->flush();

        $token    = $this->getToken('alice@example.com', 'password123');
        $response = static::createClient()->request('POST', '/api/work-entries', [
            'json'    => ['startDate' => '2026-06-22T09:00:00+00:00', 'endDate' => '2026-06-22T17:00:00+00:00'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('overlap', strtolower($response->toArray(throw: false)['detail'] ?? ''));
    }

    //
    //  existing: 09:00 ├────────────────────┤ 17:00
    //       new: 09:00 ├────────────────────┤ 17:00
    //                  ↑ overlap (exact same interval)
    //
    public function testOverlapSameInterval(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T09:00:00+00:00'), new \DateTimeImmutable('2026-06-22T17:00:00+00:00')));
        $em->flush();

        $token    = $this->getToken('alice@example.com', 'password123');
        $response = static::createClient()->request('POST', '/api/work-entries', [
            'json'    => ['startDate' => '2026-06-22T09:00:00+00:00', 'endDate' => '2026-06-22T17:00:00+00:00'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('overlap', strtolower($response->toArray(throw: false)['detail'] ?? ''));
    }

    //
    //  existing: 09:00 ├────────────────────┤ 17:00
    //       new:              12:00 ├───────────────────────────→ ∞
    //                               ↑ overlap (new open-ended, starts inside existing)
    //
    public function testOverlapNewOpenEndedStartsInsideExisting(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T09:00:00+00:00'), new \DateTimeImmutable('2026-06-22T17:00:00+00:00')));
        $em->flush();

        $token    = $this->getToken('alice@example.com', 'password123');
        $response = static::createClient()->request('POST', '/api/work-entries', [
            'json'    => ['startDate' => '2026-06-22T12:00:00+00:00'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('overlap', strtolower($response->toArray(throw: false)['detail'] ?? ''));
    }

    //
    //  existing:       09:00 ├────────────────────┤ 17:00
    //       new: 07:00 ├──────────────────────────────────────→ ∞
    //                         ↑ overlap (new open-ended, starts before and covers existing)
    //
    public function testOverlapNewOpenEndedCoversExisting(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T09:00:00+00:00'), new \DateTimeImmutable('2026-06-22T17:00:00+00:00')));
        $em->flush();

        $token    = $this->getToken('alice@example.com', 'password123');
        $response = static::createClient()->request('POST', '/api/work-entries', [
            'json'    => ['startDate' => '2026-06-22T07:00:00+00:00'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('overlap', strtolower($response->toArray(throw: false)['detail'] ?? ''));
    }

    //
    //  existing: 09:00 ├──────────────────────────────────────→ ∞
    //       new:              12:00 ├──────┤ 17:00
    //                               ↑ overlap (existing open-ended, new starts inside it)
    //
    public function testOverlapExistingOpenEndedNewInside(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T09:00:00+00:00')));
        $em->flush();

        $token    = $this->getToken('alice@example.com', 'password123');
        $response = static::createClient()->request('POST', '/api/work-entries', [
            'json'    => ['startDate' => '2026-06-22T12:00:00+00:00', 'endDate' => '2026-06-22T17:00:00+00:00'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('overlap', strtolower($response->toArray(throw: false)['detail'] ?? ''));
    }

    //
    //  existing: 09:00 ├──────────────────────────────────────→ ∞
    //       new:              12:00 ├───────────────────────────→ ∞
    //                               ↑ overlap (both open-ended)
    //
    public function testOverlapBothOpenEnded(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T09:00:00+00:00')));
        $em->flush();

        $token    = $this->getToken('alice@example.com', 'password123');
        $response = static::createClient()->request('POST', '/api/work-entries', [
            'json'    => ['startDate' => '2026-06-22T12:00:00+00:00'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('overlap', strtolower($response->toArray(throw: false)['detail'] ?? ''));
    }

    // -------------------------------------------------------------------------
    // POST — no overlap (expect 201 Created)
    // -------------------------------------------------------------------------

    //
    //  existing:          09:00 ├────────────────────┤ 17:00
    //       new: 06:00 ├──────┤ 08:00
    //             no overlap (new ends before existing starts)
    //
    public function testNoOverlapNewBeforeExisting(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T09:00:00+00:00'), new \DateTimeImmutable('2026-06-22T17:00:00+00:00')));
        $em->flush();

        $token = $this->getToken('alice@example.com', 'password123');
        static::createClient()->request('POST', '/api/work-entries', [
            'json'    => ['startDate' => '2026-06-22T06:00:00+00:00', 'endDate' => '2026-06-22T08:00:00+00:00'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(201);
    }

    //
    //  existing: 09:00 ├────────────────────┤ 17:00
    //       new:                               18:00 ├──────┤ 20:00
    //             no overlap (new starts after existing ends)
    //
    public function testNoOverlapNewAfterExisting(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T09:00:00+00:00'), new \DateTimeImmutable('2026-06-22T17:00:00+00:00')));
        $em->flush();

        $token = $this->getToken('alice@example.com', 'password123');
        static::createClient()->request('POST', '/api/work-entries', [
            'json'    => ['startDate' => '2026-06-22T18:00:00+00:00', 'endDate' => '2026-06-22T20:00:00+00:00'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(201);
    }

    //
    //  existing: 09:00 ├────────────────────┤ 17:00
    //       new:                             17:00 ├──────┤ 20:00
    //             no overlap (new starts exactly when existing ends — adjacent)
    //
    public function testNoOverlapAdjacentRight(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T09:00:00+00:00'), new \DateTimeImmutable('2026-06-22T17:00:00+00:00')));
        $em->flush();

        $token = $this->getToken('alice@example.com', 'password123');
        static::createClient()->request('POST', '/api/work-entries', [
            'json'    => ['startDate' => '2026-06-22T17:00:00+00:00', 'endDate' => '2026-06-22T20:00:00+00:00'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(201);
    }

    //
    //  existing:         09:00 ├────────────────────┤ 17:00
    //       new: 06:00 ├──────┤ 09:00
    //             no overlap (new ends exactly when existing starts — adjacent)
    //
    public function testNoOverlapAdjacentLeft(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T09:00:00+00:00'), new \DateTimeImmutable('2026-06-22T17:00:00+00:00')));
        $em->flush();

        $token = $this->getToken('alice@example.com', 'password123');
        static::createClient()->request('POST', '/api/work-entries', [
            'json'    => ['startDate' => '2026-06-22T06:00:00+00:00', 'endDate' => '2026-06-22T09:00:00+00:00'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(201);
    }

    //
    //  existing:          09:00 ├──────────────────────────────→ ∞
    //       new: 06:00 ├──────┤ 08:00
    //             no overlap (new ends before open-ended existing starts)
    //
    public function testNoOverlapExistingOpenEndedNewBefore(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T09:00:00+00:00')));
        $em->flush();

        $token = $this->getToken('alice@example.com', 'password123');
        static::createClient()->request('POST', '/api/work-entries', [
            'json'    => ['startDate' => '2026-06-22T06:00:00+00:00', 'endDate' => '2026-06-22T08:00:00+00:00'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(201);
    }

    //
    //  existing: 06:00 ├──────┤ 08:00
    //       new:                  09:00 ├──────────────────────→ ∞
    //             no overlap (new open-ended starts after existing ends)
    //
    public function testNoOverlapNewOpenEndedExistingBefore(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T06:00:00+00:00'), new \DateTimeImmutable('2026-06-22T08:00:00+00:00')));
        $em->flush();

        $token = $this->getToken('alice@example.com', 'password123');
        static::createClient()->request('POST', '/api/work-entries', [
            'json'    => ['startDate' => '2026-06-22T09:00:00+00:00'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(201);
    }

    //
    //  alice: 09:00 ├────────────────────┤ 17:00
    //  bob:   09:00 ├────────────────────┤ 17:00   ← different user, allowed
    //             no overlap (same window but different users)
    //
    public function testNoOverlapDifferentUsers(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $this->createUser('bob@example.com', 'password123', 'Bob');
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T09:00:00+00:00'), new \DateTimeImmutable('2026-06-22T17:00:00+00:00')));
        $em->flush();

        $token = $this->getToken('bob@example.com', 'password123');
        static::createClient()->request('POST', '/api/work-entries', [
            'json'    => ['startDate' => '2026-06-22T09:00:00+00:00', 'endDate' => '2026-06-22T17:00:00+00:00'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(201);
    }

    public function testCreateWorkEntryWithInvalidDateReturnsBadRequest(): void
    {
        $this->createUser('alice@example.com', 'password123', 'Alice');
        $token = $this->getToken('alice@example.com', 'password123');

        $response = static::createClient()->request('POST', '/api/work-entries', [
            'json'    => ['startDate' => 'not-a-date'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $data = $response->toArray(throw: false);
        $this->assertStringContainsString('startDate', $data['detail']);
    }

    // -------------------------------------------------------------------------
    // PUT / PATCH — happy path and authorisation
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
            '/api/work-entries/' . $entry->getId()->toRfc4122(),
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
            '/api/work-entries/' . $entryId,
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
    // PUT — overlap (expect 422 Unprocessable Entity)
    // -------------------------------------------------------------------------

    //
    //  other:          09:00 ├────────────────────┤ 17:00
    //  PUT to:  07:00 ├────────────┤ 12:00
    //                      ↑ overlap (updated entry starts before other, ends inside)
    //
    public function testPutOverlapPartialLeft(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $entry = new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T06:00:00+00:00'), new \DateTimeImmutable('2026-06-22T08:00:00+00:00'));
        $em->persist($entry);
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T09:00:00+00:00'), new \DateTimeImmutable('2026-06-22T17:00:00+00:00')));
        $em->flush();

        $token    = $this->getToken('alice@example.com', 'password123');
        $response = static::createClient()->request('PUT', '/api/work-entries/' . $entry->getId()->toRfc4122(), [
            'json'    => ['startDate' => '2026-06-22T07:00:00+00:00', 'endDate' => '2026-06-22T12:00:00+00:00'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('overlap', strtolower($response->toArray(throw: false)['detail'] ?? ''));
    }

    //
    //  other:  09:00 ├────────────────────┤ 17:00
    //  PUT to:              12:00 ├──────────────────────┤ 18:00
    //                              ↑ overlap (updated entry starts inside other, ends after)
    //
    public function testPutOverlapPartialRight(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $entry = new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T06:00:00+00:00'), new \DateTimeImmutable('2026-06-22T08:00:00+00:00'));
        $em->persist($entry);
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T09:00:00+00:00'), new \DateTimeImmutable('2026-06-22T17:00:00+00:00')));
        $em->flush();

        $token    = $this->getToken('alice@example.com', 'password123');
        $response = static::createClient()->request('PUT', '/api/work-entries/' . $entry->getId()->toRfc4122(), [
            'json'    => ['startDate' => '2026-06-22T12:00:00+00:00', 'endDate' => '2026-06-22T18:00:00+00:00'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('overlap', strtolower($response->toArray(throw: false)['detail'] ?? ''));
    }

    //
    //  other:  09:00 ├────────────────────────────┤ 17:00
    //  PUT to:              11:00 ├──────┤ 15:00
    //                              ↑ overlap (updated entry completely inside other)
    //
    public function testPutOverlapInsideOther(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $entry = new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T06:00:00+00:00'), new \DateTimeImmutable('2026-06-22T08:00:00+00:00'));
        $em->persist($entry);
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T09:00:00+00:00'), new \DateTimeImmutable('2026-06-22T17:00:00+00:00')));
        $em->flush();

        $token    = $this->getToken('alice@example.com', 'password123');
        $response = static::createClient()->request('PUT', '/api/work-entries/' . $entry->getId()->toRfc4122(), [
            'json'    => ['startDate' => '2026-06-22T11:00:00+00:00', 'endDate' => '2026-06-22T15:00:00+00:00'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('overlap', strtolower($response->toArray(throw: false)['detail'] ?? ''));
    }

    //
    //  other:         11:00 ├──────┤ 15:00
    //  PUT to: 09:00 ├────────────────────────────┤ 17:00
    //                        ↑ overlap (other completely inside updated entry)
    //
    public function testPutOverlapOtherInsideEntry(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $entry = new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T06:00:00+00:00'), new \DateTimeImmutable('2026-06-22T08:00:00+00:00'));
        $em->persist($entry);
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T11:00:00+00:00'), new \DateTimeImmutable('2026-06-22T15:00:00+00:00')));
        $em->flush();

        $token    = $this->getToken('alice@example.com', 'password123');
        $response = static::createClient()->request('PUT', '/api/work-entries/' . $entry->getId()->toRfc4122(), [
            'json'    => ['startDate' => '2026-06-22T09:00:00+00:00', 'endDate' => '2026-06-22T17:00:00+00:00'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('overlap', strtolower($response->toArray(throw: false)['detail'] ?? ''));
    }

    //
    //  other:  09:00 ├────────────────────┤ 17:00
    //  PUT to: 09:00 ├────────────────────┤ 17:00
    //                 ↑ overlap (same interval as other entry)
    //
    public function testPutOverlapSameInterval(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $entry = new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T06:00:00+00:00'), new \DateTimeImmutable('2026-06-22T08:00:00+00:00'));
        $em->persist($entry);
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T09:00:00+00:00'), new \DateTimeImmutable('2026-06-22T17:00:00+00:00')));
        $em->flush();

        $token    = $this->getToken('alice@example.com', 'password123');
        $response = static::createClient()->request('PUT', '/api/work-entries/' . $entry->getId()->toRfc4122(), [
            'json'    => ['startDate' => '2026-06-22T09:00:00+00:00', 'endDate' => '2026-06-22T17:00:00+00:00'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('overlap', strtolower($response->toArray(throw: false)['detail'] ?? ''));
    }

    //
    //  other:  09:00 ├────────────────────┤ 17:00
    //  PUT to:              12:00 ├───────────────────────────→ ∞
    //                              ↑ overlap (updated open-ended, starts inside other)
    //
    public function testPutOverlapOpenEndedStartsInsideOther(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $entry = new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T06:00:00+00:00'), new \DateTimeImmutable('2026-06-22T08:00:00+00:00'));
        $em->persist($entry);
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T09:00:00+00:00'), new \DateTimeImmutable('2026-06-22T17:00:00+00:00')));
        $em->flush();

        $token    = $this->getToken('alice@example.com', 'password123');
        $response = static::createClient()->request('PUT', '/api/work-entries/' . $entry->getId()->toRfc4122(), [
            'json'    => ['startDate' => '2026-06-22T12:00:00+00:00'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('overlap', strtolower($response->toArray(throw: false)['detail'] ?? ''));
    }

    //
    //  other:         09:00 ├────────────────────┤ 17:00
    //  PUT to: 07:00 ├──────────────────────────────────────→ ∞
    //                        ↑ overlap (updated open-ended covers entire other)
    //
    public function testPutOverlapOpenEndedCoversOther(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $entry = new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T06:00:00+00:00'), new \DateTimeImmutable('2026-06-22T08:00:00+00:00'));
        $em->persist($entry);
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T09:00:00+00:00'), new \DateTimeImmutable('2026-06-22T17:00:00+00:00')));
        $em->flush();

        $token    = $this->getToken('alice@example.com', 'password123');
        $response = static::createClient()->request('PUT', '/api/work-entries/' . $entry->getId()->toRfc4122(), [
            'json'    => ['startDate' => '2026-06-22T07:00:00+00:00'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('overlap', strtolower($response->toArray(throw: false)['detail'] ?? ''));
    }

    //
    //  other:  09:00 ├──────────────────────────────────────→ ∞
    //  PUT to:              12:00 ├──────┤ 17:00
    //                              ↑ overlap (other open-ended, updated entry inside it)
    //
    public function testPutOverlapOtherOpenEndedEntryInside(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $entry = new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T06:00:00+00:00'), new \DateTimeImmutable('2026-06-22T08:00:00+00:00'));
        $em->persist($entry);
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T09:00:00+00:00')));
        $em->flush();

        $token    = $this->getToken('alice@example.com', 'password123');
        $response = static::createClient()->request('PUT', '/api/work-entries/' . $entry->getId()->toRfc4122(), [
            'json'    => ['startDate' => '2026-06-22T12:00:00+00:00', 'endDate' => '2026-06-22T17:00:00+00:00'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('overlap', strtolower($response->toArray(throw: false)['detail'] ?? ''));
    }

    //
    //  other:  09:00 ├──────────────────────────────────────→ ∞
    //  PUT to:              12:00 ├───────────────────────────→ ∞
    //                              ↑ overlap (both open-ended)
    //
    public function testPutOverlapBothOpenEnded(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $entry = new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T06:00:00+00:00'), new \DateTimeImmutable('2026-06-22T08:00:00+00:00'));
        $em->persist($entry);
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T09:00:00+00:00')));
        $em->flush();

        $token    = $this->getToken('alice@example.com', 'password123');
        $response = static::createClient()->request('PUT', '/api/work-entries/' . $entry->getId()->toRfc4122(), [
            'json'    => ['startDate' => '2026-06-22T12:00:00+00:00'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('overlap', strtolower($response->toArray(throw: false)['detail'] ?? ''));
    }

    // -------------------------------------------------------------------------
    // PUT — no overlap (expect 200 OK)
    // -------------------------------------------------------------------------

    //
    //  entry:  09:00 ├────────────────────┤ 17:00  (no other entries)
    //  PUT to: 09:00 ├────────────────────┤ 17:00  (same dates — no self-conflict)
    //                 self-update: excluded from overlap check by its own ID
    //
    public function testPutNoOverlapSelfUpdate(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $entry = new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T09:00:00+00:00'), new \DateTimeImmutable('2026-06-22T17:00:00+00:00'));
        $em->persist($entry);
        $em->flush();

        $token = $this->getToken('alice@example.com', 'password123');
        static::createClient()->request('PUT', '/api/work-entries/' . $entry->getId()->toRfc4122(), [
            'json'    => ['startDate' => '2026-06-22T09:00:00+00:00', 'endDate' => '2026-06-22T17:00:00+00:00'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(200);
    }

    //
    //  other:         09:00 ├────────────────────┤ 17:00
    //  PUT to: 06:00 ├──────┤ 08:00
    //           no overlap (updated entry ends before other starts)
    //
    public function testPutNoOverlapMovedBefore(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $entry = new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T18:00:00+00:00'), new \DateTimeImmutable('2026-06-22T20:00:00+00:00'));
        $em->persist($entry);
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T09:00:00+00:00'), new \DateTimeImmutable('2026-06-22T17:00:00+00:00')));
        $em->flush();

        $token = $this->getToken('alice@example.com', 'password123');
        static::createClient()->request('PUT', '/api/work-entries/' . $entry->getId()->toRfc4122(), [
            'json'    => ['startDate' => '2026-06-22T06:00:00+00:00', 'endDate' => '2026-06-22T08:00:00+00:00'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(200);
    }

    //
    //  other:  09:00 ├────────────────────┤ 17:00
    //  PUT to:                              18:00 ├──────┤ 20:00
    //           no overlap (updated entry starts after other ends)
    //
    public function testPutNoOverlapMovedAfter(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $entry = new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T06:00:00+00:00'), new \DateTimeImmutable('2026-06-22T08:00:00+00:00'));
        $em->persist($entry);
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T09:00:00+00:00'), new \DateTimeImmutable('2026-06-22T17:00:00+00:00')));
        $em->flush();

        $token = $this->getToken('alice@example.com', 'password123');
        static::createClient()->request('PUT', '/api/work-entries/' . $entry->getId()->toRfc4122(), [
            'json'    => ['startDate' => '2026-06-22T18:00:00+00:00', 'endDate' => '2026-06-22T20:00:00+00:00'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(200);
    }

    //
    //  other:  09:00 ├────────────────────┤ 17:00
    //  PUT to:                             17:00 ├──────┤ 20:00
    //           no overlap (updated entry starts exactly when other ends — adjacent)
    //
    public function testPutNoOverlapAdjacentRight(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $entry = new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T06:00:00+00:00'), new \DateTimeImmutable('2026-06-22T08:00:00+00:00'));
        $em->persist($entry);
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T09:00:00+00:00'), new \DateTimeImmutable('2026-06-22T17:00:00+00:00')));
        $em->flush();

        $token = $this->getToken('alice@example.com', 'password123');
        static::createClient()->request('PUT', '/api/work-entries/' . $entry->getId()->toRfc4122(), [
            'json'    => ['startDate' => '2026-06-22T17:00:00+00:00', 'endDate' => '2026-06-22T20:00:00+00:00'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(200);
    }

    //
    //  other:         09:00 ├────────────────────┤ 17:00
    //  PUT to: 06:00 ├──────┤ 09:00
    //           no overlap (updated entry ends exactly when other starts — adjacent)
    //
    public function testPutNoOverlapAdjacentLeft(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $entry = new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T18:00:00+00:00'), new \DateTimeImmutable('2026-06-22T20:00:00+00:00'));
        $em->persist($entry);
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T09:00:00+00:00'), new \DateTimeImmutable('2026-06-22T17:00:00+00:00')));
        $em->flush();

        $token = $this->getToken('alice@example.com', 'password123');
        static::createClient()->request('PUT', '/api/work-entries/' . $entry->getId()->toRfc4122(), [
            'json'    => ['startDate' => '2026-06-22T06:00:00+00:00', 'endDate' => '2026-06-22T09:00:00+00:00'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(200);
    }

    //
    //  other:         09:00 ├──────────────────────────────→ ∞
    //  PUT to: 06:00 ├──────┤ 08:00
    //           no overlap (updated entry ends before open-ended other starts)
    //
    public function testPutNoOverlapOtherOpenEndedEntryBefore(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $entry = new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T18:00:00+00:00'), new \DateTimeImmutable('2026-06-22T20:00:00+00:00'));
        $em->persist($entry);
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T09:00:00+00:00')));
        $em->flush();

        $token = $this->getToken('alice@example.com', 'password123');
        static::createClient()->request('PUT', '/api/work-entries/' . $entry->getId()->toRfc4122(), [
            'json'    => ['startDate' => '2026-06-22T06:00:00+00:00', 'endDate' => '2026-06-22T08:00:00+00:00'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(200);
    }

    //
    //  other:  06:00 ├──────┤ 08:00
    //  PUT to:                  09:00 ├──────────────────────→ ∞
    //           no overlap (updated open-ended starts after other ends)
    //
    public function testPutNoOverlapEntryOpenEndedOtherBefore(): void
    {
        $em    = $this->getEntityManager();
        $alice = $this->createUser('alice@example.com', 'password123', 'Alice');
        $entry = new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T18:00:00+00:00'), new \DateTimeImmutable('2026-06-22T20:00:00+00:00'));
        $em->persist($entry);
        $em->persist(new WorkEntry($alice, new \DateTimeImmutable('2026-06-22T06:00:00+00:00'), new \DateTimeImmutable('2026-06-22T08:00:00+00:00')));
        $em->flush();

        $token = $this->getToken('alice@example.com', 'password123');
        static::createClient()->request('PUT', '/api/work-entries/' . $entry->getId()->toRfc4122(), [
            'json'    => ['startDate' => '2026-06-22T09:00:00+00:00'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(200);
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

        static::createClient()->request('DELETE', '/api/work-entries/' . $id, $this->authHeaders($token));
        $this->assertResponseStatusCodeSame(204);

        static::createClient()->request('GET', '/api/work-entries/' . $id, $this->authHeaders($token));
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
            '/api/work-entries/' . $entryId,
            $this->authHeaders($token),
        );

        $this->assertResponseStatusCodeSame(403);
    }
}
