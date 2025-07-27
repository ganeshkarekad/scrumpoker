<?php

namespace App\Tests\Controller\Api;

use App\Entity\Room;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RoomControllerTest extends WebTestCase
{
    private function getEntityManager(): EntityManagerInterface
    {
        $kernel = self::bootKernel();
        return $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    private function setupDatabase(EntityManagerInterface $entityManager): void
    {
        $schemaTool = new SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);
    }

    public function testParticipantsEndpoint(): void
    {
        $client = static::createClient();
        $entityManager = $this->getEntityManager();
        $this->setupDatabase($entityManager);

        // Create test users
        $creator = new User();
        $creator->setUsername('TestCreator');
        $entityManager->persist($creator);

        $participant = new User();
        $participant->setUsername('TestParticipant');
        $entityManager->persist($participant);

        // Create test room
        $room = new Room();
        $room->setCreatedBy($creator);
        $room->addUser($creator);
        $room->addUser($participant);
        $entityManager->persist($room);

        $entityManager->flush();

        // Test the API endpoint
        $client->request('GET', '/api/room/' . $room->getRoomKey() . '/participants');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseData = json_decode($client->getResponse()->getContent(), true);

        // Assert response structure
        $this->assertArrayHasKey('roomKey', $responseData);
        $this->assertArrayHasKey('participants', $responseData);
        $this->assertArrayHasKey('status', $responseData);
        $this->assertArrayHasKey('createdBy', $responseData);

        // Assert room data
        $this->assertEquals($room->getRoomKey(), $responseData['roomKey']);
        $this->assertEquals($creator->getUserLoginId(), $responseData['createdBy']);
        $this->assertEquals('active', $responseData['status']);

        // Assert participants
        $this->assertCount(2, $responseData['participants']);

        // Find creator in participants
        $creatorFound = false;
        $participantFound = false;

        foreach ($responseData['participants'] as $participant) {
            $this->assertArrayHasKey('id', $participant);
            $this->assertArrayHasKey('username', $participant);
            $this->assertArrayHasKey('isCreator', $participant);

            if ($participant['username'] === 'TestCreator') {
                $this->assertTrue($participant['isCreator']);
                $creatorFound = true;
            }

            if ($participant['username'] === 'TestParticipant') {
                $this->assertFalse($participant['isCreator']);
                $participantFound = true;
            }
        }

        $this->assertTrue($creatorFound, 'Creator not found in participants');
        $this->assertTrue($participantFound, 'Participant not found in participants');
    }

    public function testParticipantsEndpointWithInvalidRoom(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/room/invalid-room-key/participants');

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Room not found', $responseData['error']);
    }

    public function testLeaveRoomEndpoint(): void
    {
        $client = static::createClient();
        $entityManager = $this->getEntityManager();
        $this->setupDatabase($entityManager);

        // Create test users
        $creator = new User();
        $creator->setUsername('TestCreator');
        $entityManager->persist($creator);

        $participant = new User();
        $participant->setUsername('TestParticipant');
        $entityManager->persist($participant);

        // Create test room
        $room = new Room();
        $room->setCreatedBy($creator);
        $room->addUser($creator);
        $room->addUser($participant);
        $entityManager->persist($room);

        $entityManager->flush();

        // Verify participant is in room initially
        $this->assertTrue($room->getUsers()->contains($participant));

        // Test leaving the room
        $client->request('POST', '/api/room/' . $room->getRoomKey() . '/leave', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'userId' => $participant->getUserLoginId(),
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseData = json_decode($client->getResponse()->getContent(), true);

        // Assert response structure
        $this->assertArrayHasKey('message', $responseData);
        $this->assertArrayHasKey('roomKey', $responseData);
        $this->assertArrayHasKey('userId', $responseData);

        // Assert response data
        $this->assertEquals('User left room successfully', $responseData['message']);
        $this->assertEquals($room->getRoomKey(), $responseData['roomKey']);
        $this->assertEquals($participant->getUserLoginId(), $responseData['userId']);

        // Refresh the room entity and verify participant was removed
        $entityManager->refresh($room);
        $this->assertFalse($room->getUsers()->contains($participant));
        $this->assertTrue($room->getUsers()->contains($creator)); // Creator should still be there
    }

    public function testLeaveRoomWithInvalidRoom(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/room/invalid-room-key/leave', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'userId' => 'some-user-id',
        ]));

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Room not found', $responseData['error']);
    }

    public function testLeaveRoomWithInvalidUser(): void
    {
        $client = static::createClient();
        $entityManager = $this->getEntityManager();
        $this->setupDatabase($entityManager);

        // Create test room
        $creator = new User();
        $creator->setUsername('TestCreator');
        $entityManager->persist($creator);

        $room = new Room();
        $room->setCreatedBy($creator);
        $room->addUser($creator);
        $entityManager->persist($room);

        $entityManager->flush();

        $client->request('POST', '/api/room/' . $room->getRoomKey() . '/leave', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'userId' => 'invalid-user-id',
        ]));

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('User not found', $responseData['error']);
    }

    public function testLeaveRoomWithUserNotInRoom(): void
    {
        $client = static::createClient();
        $entityManager = $this->getEntityManager();
        $this->setupDatabase($entityManager);

        // Create test users
        $creator = new User();
        $creator->setUsername('TestCreator');
        $entityManager->persist($creator);

        $otherUser = new User();
        $otherUser->setUsername('OtherUser');
        $entityManager->persist($otherUser);

        // Create test room with only creator
        $room = new Room();
        $room->setCreatedBy($creator);
        $room->addUser($creator);
        $entityManager->persist($room);

        $entityManager->flush();

        // Try to leave with user not in room - should succeed gracefully
        $client->request('POST', '/api/room/' . $room->getRoomKey() . '/leave', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'userId' => $otherUser->getUserLoginId(),
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('User already left room or was not in room', $responseData['message']);
        $this->assertEquals($room->getRoomKey(), $responseData['roomKey']);
        $this->assertEquals($otherUser->getUserLoginId(), $responseData['userId']);
    }


}
