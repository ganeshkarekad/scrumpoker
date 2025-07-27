<?php

namespace App\Controller\Api;

use App\Repository\RoomRepository;
use App\Repository\UserRepository;
use App\Service\MercurePublisher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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

    #[Route('/{roomKey}/leave', name: 'leave', methods: ['POST'])]
    public function leave(
        string $roomKey,
        Request $request,
        RoomRepository $roomRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        MercurePublisher $mercurePublisher
    ): JsonResponse {
        // Get JSON data from request
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['userId'])) {
            return $this->json([
                'error' => 'Missing userId'
            ], Response::HTTP_BAD_REQUEST);
        }

        $userId = $data['userId'];

        // Find the room
        $room = $roomRepository->findOneBy(['roomKey' => $roomKey]);
        if (!$room) {
            return $this->json([
                'error' => 'Room not found'
            ], Response::HTTP_NOT_FOUND);
        }

        // Find the user
        $user = $userRepository->findByUserLoginId($userId);
        if (!$user) {
            return $this->json([
                'error' => 'User not found'
            ], Response::HTTP_NOT_FOUND);
        }

        // Check if user is in the room
        if (!$room->getUsers()->contains($user)) {
            // User is not in room - this is fine, they may have already left
            // Return success to avoid errors on duplicate leave calls
            return $this->json([
                'message' => 'User already left room or was not in room',
                'roomKey' => $roomKey,
                'userId' => $userId,
            ]);
        }

        // Prepare participant data before removal
        $participantData = [
            'id' => $user->getUserLoginId(),
            'username' => $user->getUsername(),
            'isCreator' => $room->getCreatedBy() === $user,
            'vote' => null,
        ];

        // Remove user from room
        $room->removeUser($user);

        // Remove any votes by this user in this room
        $userVotes = $room->getUserVotes();
        foreach ($userVotes as $userVote) {
            if ($userVote->getUser() === $user) {
                $entityManager->remove($userVote);
            }
        }

        $entityManager->flush();

        // Publish participant update for user leaving
        $mercurePublisher->publishParticipantUpdate($roomKey, $participantData, 'left');

        return $this->json([
            'message' => 'User left room successfully',
            'roomKey' => $roomKey,
            'userId' => $userId,
        ]);
    }
}
