<?php

namespace App\Tests\Controller\Api;

use App\Entity\Room;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RoomControllerTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    public function testParticipantsEndpoint(): void
    {
        $client = static::createClient();

        // Create test users
        $creator = new User();
        $creator->setUsername('TestCreator');
        $this->entityManager->persist($creator);

        $participant = new User();
        $participant->setUsername('TestParticipant');
        $this->entityManager->persist($participant);

        // Create test room
        $room = new Room();
        $room->setCreatedBy($creator);
        $room->addUser($creator);
        $room->addUser($participant);
        $this->entityManager->persist($room);

        $this->entityManager->flush();

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

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up test data
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
