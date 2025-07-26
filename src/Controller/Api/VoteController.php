<?php

namespace App\Controller\Api;

use App\Entity\UserVote;
use App\Repository\RoomRepository;
use App\Repository\UserRepository;
use App\Repository\VoteRepository;
use App\Service\MercurePublisher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/vote', name: 'api_vote_')]
class VoteController extends AbstractController
{
    #[Route('/list', name: 'list', methods: ['GET'])]
    public function listVotes(VoteRepository $voteRepository): JsonResponse
    {
        // Get all votes ordered by their sequence
        $votes = $voteRepository->findAllOrdered();

        $votesData = [];
        foreach ($votes as $vote) {
            $votesData[] = [
                'id' => $vote->getId(),
                'label' => $vote->getLabel(),
            ];
        }

        return $this->json($votesData);
    }

    #[Route('/toggle-visibility', name: 'toggle_visibility', methods: ['POST'])]
    public function toggleVisibility(
        Request $request,
        EntityManagerInterface $entityManager,
        RoomRepository $roomRepository,
        MercurePublisher $mercurePublisher
    ): JsonResponse {
        // Get JSON data from request
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['roomKey'])) {
            return $this->json([
                'error' => 'Missing roomKey'
            ], Response::HTTP_BAD_REQUEST);
        }

        $roomKey = $data['roomKey'];

        // Find the room
        $room = $roomRepository->findOneBy(['roomKey' => $roomKey]);
        if (!$room) {
            return $this->json([
                'error' => 'Room not found'
            ], Response::HTTP_NOT_FOUND);
        }

        // Toggle the votes visibility
        $currentVisibility = $room->getVotesVisible() ?? false;
        $room->setVotesVisible(!$currentVisibility);

        $entityManager->flush();

        // Publish Mercure update for visibility toggle
        $mercurePublisher->publishVisibilityToggle($roomKey, $room->getVotesVisible());

        return $this->json([
            'message' => 'Vote visibility toggled successfully',
            'roomKey' => $roomKey,
            'votesVisible' => $room->getVotesVisible()
        ]);
    }

    #[Route('/reset', name: 'reset', methods: ['POST'])]
    public function reset(
        Request $request,
        EntityManagerInterface $entityManager,
        RoomRepository $roomRepository,
        MercurePublisher $mercurePublisher
    ): JsonResponse {
        // Get JSON data from request
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['roomKey'])) {
            return $this->json([
                'error' => 'Missing roomKey'
            ], Response::HTTP_BAD_REQUEST);
        }

        $roomKey = $data['roomKey'];

        // Find the room
        $room = $roomRepository->findOneBy(['roomKey' => $roomKey]);
        if (!$room) {
            return $this->json([
                'error' => 'Room not found'
            ], Response::HTTP_NOT_FOUND);
        }

        // Delete all user votes for this room and hide votes
        $userVotes = $room->getUserVotes();
        foreach ($userVotes as $userVote) {
            $entityManager->remove($userVote);
        }

        // Hide votes after reset
        $room->setVotesVisible(false);

        $entityManager->flush();

        // Publish Mercure update for vote reset
        $mercurePublisher->publishVoteReset($roomKey);

        return $this->json([
            'message' => 'All votes reset successfully',
            'roomKey' => $roomKey,
            'resetCount' => count($userVotes),
            'votesVisible' => false
        ]);
    }

    #[Route('/add', name: 'add', methods: ['POST'])]
    public function add(
        Request $request,
        EntityManagerInterface $entityManager,
        RoomRepository $roomRepository,
        UserRepository $userRepository,
        VoteRepository $voteRepository,
        MercurePublisher $mercurePublisher
    ): JsonResponse {
        // Get JSON data from request
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json([
                'error' => 'Invalid JSON data'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validate required fields
        $requiredFields = ['roomKey', 'userId', 'voteId'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return $this->json([
                    'error' => "Missing required field: {$field}"
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        $roomKey = $data['roomKey'];
        $userId = $data['userId'];
        $voteId = (int) $data['voteId'];

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

        // Find the vote
        $vote = $voteRepository->find($voteId);
        if (!$vote) {
            return $this->json([
                'error' => 'Vote not found'
            ], Response::HTTP_NOT_FOUND);
        }

        // Check if user is a participant in the room
        if (!$room->getUsers()->contains($user)) {
            return $this->json([
                'error' => 'User is not a participant in this room'
            ], Response::HTTP_FORBIDDEN);
        }

        // Check if user has already voted in this room
        $existingUserVote = $room->getUserVote($user);

        if ($existingUserVote) {
            // Update existing vote
            $existingUserVote->setVote($vote);
            $existingUserVote->setUpdatedAt(new \DateTime());

            $entityManager->flush();

            // Publish Mercure update for vote change
            $voteData = [
                'id' => $existingUserVote->getId(),
                'roomKey' => $room->getRoomKey(),
                'userId' => $user->getUserLoginId(),
                'username' => $user->getUsername(),
                'voteId' => $vote->getId(),
                'voteLabel' => $vote->getLabel(),
                'createdAt' => $existingUserVote->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $existingUserVote->getUpdatedAt()->format('Y-m-d H:i:s'),
            ];
            $mercurePublisher->publishVoteUpdate($roomKey, $voteData);

            return $this->json([
                'message' => 'Vote updated successfully',
                'userVote' => $voteData
            ], Response::HTTP_OK);
        } else {
            // Create new vote
            $userVote = new UserVote();
            $userVote->setRoom($room);
            $userVote->setUser($user);
            $userVote->setVote($vote);
            // createdAt and updatedAt are set automatically in the constructor

            $entityManager->persist($userVote);
            $entityManager->flush();

            // Publish Mercure update for new vote
            $voteData = [
                'id' => $userVote->getId(),
                'roomKey' => $room->getRoomKey(),
                'userId' => $user->getUserLoginId(),
                'username' => $user->getUsername(),
                'voteId' => $vote->getId(),
                'voteLabel' => $vote->getLabel(),
                'createdAt' => $userVote->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $userVote->getUpdatedAt()->format('Y-m-d H:i:s'),
            ];
            $mercurePublisher->publishVoteUpdate($roomKey, $voteData);

            return $this->json([
                'message' => 'Vote added successfully',
                'userVote' => $voteData
            ], Response::HTTP_CREATED);
        }
    }
}
