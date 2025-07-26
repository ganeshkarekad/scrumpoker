<?php

namespace App\Controller\Api;

use App\Repository\RoomRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/room', name: 'api_room_')]
class RoomController extends AbstractController
{
    #[Route('/{roomKey}/participants', name: 'participants', methods: ['GET'])]
    public function participants(string $roomKey, RoomRepository $roomRepository): JsonResponse
    {
        // Find the room by roomKey
        $room = $roomRepository->findOneBy(['roomKey' => $roomKey]);

        if (!$room) {
            return $this->json([
                'error' => 'Room not found'
            ], Response::HTTP_NOT_FOUND);
        }

        // Get all participants (users) in the room with their votes
        $participants = [];
        foreach ($room->getUsers() as $user) {
            $userVote = $room->getUserVote($user);

            $participants[] = [
                'id' => $user->getUserLoginId(),
                'username' => $user->getUsername(),
                'isCreator' => $room->getCreatedBy() === $user,
                'vote' => $userVote ? [
                    'id' => $userVote->getVote()->getId(),
                    'label' => $userVote->getVote()->getLabel(),
                    'createdAt' => $userVote->getCreatedAt()->format('Y-m-d H:i:s'),
                    'updatedAt' => $userVote->getUpdatedAt()->format('Y-m-d H:i:s'),
                ] : null,
            ];
        }

        return $this->json([
            'roomKey' => $room->getRoomKey(),
            'participants' => $participants,
            'status' => 'active', // You can add actual status field to Room entity later
            'createdBy' => $room->getCreatedBy()->getUserLoginId(),
            'votesVisible' => $room->getVotesVisible() ?? false,
        ]);
    }
}
