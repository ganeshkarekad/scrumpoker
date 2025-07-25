<?php

namespace App\Tests\Entity;

use App\Entity\Room;
use App\Entity\RoomAdmin;
use App\Entity\User;
use App\Entity\UserVote;
use App\Entity\Vote;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class ScrumPokerEntitiesTest extends TestCase
{
    public function testVoteEntity(): void
    {
        $vote = new Vote();
        $vote->setLabel('5');

        $this->assertEquals('5', $vote->getLabel());
        $this->assertEquals('5', (string) $vote);
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $vote->getUserVotes());
    }

    public function testUserEntity(): void
    {
        $user = new User();
        $user->setUsername('testuser');

        $this->assertEquals('testuser', $user->getUsername());
        $this->assertEquals('testuser', (string) $user);
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getCreatedAt());
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $user->getUserRooms());
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $user->getCreatedRooms());
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $user->getRoomAdmins());
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $user->getUserVotes());
    }

    public function testRoomEntity(): void
    {
        $user = new User();
        $user->setUsername('creator');

        $room = new Room();
        $room->setCreatedBy($user);

        $this->assertInstanceOf(\DateTimeImmutable::class, $room->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $room->getUpdatedAt());
        $this->assertEquals($user, $room->getCreatedBy());
        $this->assertTrue(Uuid::isValid($room->getRoomKey()));
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $room->getUsers());
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $room->getRoomAdmins());
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $room->getUserVotes());
    }

    public function testRoomAdminEntity(): void
    {
        $user = new User();
        $user->setUsername('admin');

        $creator = new User();
        $creator->setUsername('creator');

        $room = new Room();
        $room->setCreatedBy($creator);

        $roomAdmin = new RoomAdmin();
        $roomAdmin->setRoom($room);
        $roomAdmin->setUser($user);

        $this->assertEquals($room, $roomAdmin->getRoom());
        $this->assertEquals($user, $roomAdmin->getUser());
        $this->assertInstanceOf(\DateTimeImmutable::class, $roomAdmin->getCreatedAt());
        $this->assertStringContainsString('admin', (string) $roomAdmin);
    }

    public function testUserVoteEntity(): void
    {
        $user = new User();
        $user->setUsername('voter');

        $creator = new User();
        $creator->setUsername('creator');

        $room = new Room();
        $room->setCreatedBy($creator);

        $vote = new Vote();
        $vote->setLabel('8');

        $userVote = new UserVote();
        $userVote->setRoom($room);
        $userVote->setUser($user);
        $userVote->setVote($vote);

        $this->assertEquals($room, $userVote->getRoom());
        $this->assertEquals($user, $userVote->getUser());
        $this->assertEquals($vote, $userVote->getVote());
        $this->assertInstanceOf(\DateTimeImmutable::class, $userVote->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $userVote->getUpdatedAt());
        $this->assertStringContainsString('voter', (string) $userVote);
        $this->assertStringContainsString('8', (string) $userVote);
    }

    public function testRoomUserRelationships(): void
    {
        $user1 = new User();
        $user1->setUsername('user1');

        $user2 = new User();
        $user2->setUsername('user2');

        $creator = new User();
        $creator->setUsername('creator');

        $room = new Room();
        $room->setCreatedBy($creator);

        // Test adding users to room
        $room->addUser($user1);
        $room->addUser($user2);

        $this->assertTrue($room->getUsers()->contains($user1));
        $this->assertTrue($room->getUsers()->contains($user2));
        $this->assertCount(2, $room->getUsers());

        // Test removing user from room
        $room->removeUser($user1);
        $this->assertFalse($room->getUsers()->contains($user1));
        $this->assertCount(1, $room->getUsers());
    }

    public function testRoomAdminFunctionality(): void
    {
        $user = new User();
        $user->setUsername('admin');

        $creator = new User();
        $creator->setUsername('creator');

        $room = new Room();
        $room->setCreatedBy($creator);

        $roomAdmin = new RoomAdmin();
        $roomAdmin->setRoom($room);
        $roomAdmin->setUser($user);

        $room->addRoomAdmin($roomAdmin);

        $this->assertTrue($room->isUserAdmin($user));
        $this->assertFalse($room->isUserAdmin($creator));
    }

    public function testUserVoteFunctionality(): void
    {
        $user = new User();
        $user->setUsername('voter');

        $creator = new User();
        $creator->setUsername('creator');

        $room = new Room();
        $room->setCreatedBy($creator);

        $vote = new Vote();
        $vote->setLabel('13');

        $userVote = new UserVote();
        $userVote->setRoom($room);
        $userVote->setUser($user);
        $userVote->setVote($vote);

        $room->addUserVote($userVote);

        $retrievedVote = $room->getUserVote($user);
        $this->assertEquals($userVote, $retrievedVote);
        $this->assertEquals('13', $retrievedVote->getVote()->getLabel());

        // Test that non-voting user returns null
        $nonVoter = new User();
        $nonVoter->setUsername('nonvoter');
        $this->assertNull($room->getUserVote($nonVoter));
    }
}
