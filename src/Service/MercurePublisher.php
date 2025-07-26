<?php

namespace App\Service;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class MercurePublisher
{
    public function __construct(
        private HubInterface $hub
    ) {
    }

    /**
     * Publish room data update to all participants
     */
    public function publishRoomUpdate(string $roomKey, array $data): void
    {
        $update = new Update(
            "room/{$roomKey}",
            json_encode([
                'type' => 'room_update',
                'roomKey' => $roomKey,
                'data' => $data,
                'timestamp' => time()
            ])
        );

        $this->hub->publish($update);
    }

    /**
     * Publish vote update to all participants in a room
     */
    public function publishVoteUpdate(string $roomKey, array $voteData): void
    {
        $update = new Update(
            "room/{$roomKey}",
            json_encode([
                'type' => 'vote_update',
                'roomKey' => $roomKey,
                'data' => $voteData,
                'timestamp' => time()
            ])
        );

        $this->hub->publish($update);
    }

    /**
     * Publish vote visibility toggle to all participants in a room
     */
    public function publishVisibilityToggle(string $roomKey, bool $visible): void
    {
        $update = new Update(
            "room/{$roomKey}",
            json_encode([
                'type' => 'visibility_toggle',
                'roomKey' => $roomKey,
                'data' => ['votesVisible' => $visible],
                'timestamp' => time()
            ])
        );

        $this->hub->publish($update);
    }

    /**
     * Publish vote reset to all participants in a room
     */
    public function publishVoteReset(string $roomKey): void
    {
        $update = new Update(
            "room/{$roomKey}",
            json_encode([
                'type' => 'vote_reset',
                'roomKey' => $roomKey,
                'data' => ['reset' => true],
                'timestamp' => time()
            ])
        );

        $this->hub->publish($update);
    }

    /**
     * Publish participant join/leave events
     */
    public function publishParticipantUpdate(string $roomKey, array $participantData, string $action): void
    {
        $update = new Update(
            "room/{$roomKey}",
            json_encode([
                'type' => 'participant_update',
                'roomKey' => $roomKey,
                'data' => [
                    'action' => $action, // 'joined' or 'left'
                    'participant' => $participantData
                ],
                'timestamp' => time()
            ])
        );

        $this->hub->publish($update);
    }
}
