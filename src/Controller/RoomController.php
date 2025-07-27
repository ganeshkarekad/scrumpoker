<?php

namespace App\Controller;

use App\Entity\Room;
use App\Entity\User;
use App\Form\RoomType;
use App\Form\UserType;
use App\Repository\RoomRepository;
use App\Repository\UserRepository;
use App\Service\MercurePublisher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/room/', name: 'app_room_')]
final class RoomController extends AbstractController
{


    #[Route('create', name: 'create')]
    public function create(Request $request, EntityManagerInterface $entityManager, SessionInterface $session, MercurePublisher $mercurePublisher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Create user
            $entityManager->persist($user);

            // Create room
            $room = new Room();
            $room->setCreatedBy($user);
            $room->addUser($user);

            $entityManager->persist($room);
            $entityManager->flush();

            // Store userLoginId in session for "login"
            $session->set('user_login_id', $user->getUserLoginId());

            // Publish participant update for room creation
            $participantData = [
                'id' => $user->getUserLoginId(),
                'username' => $user->getUsername(),
                'isCreator' => true,
                'vote' => null,
            ];
            $mercurePublisher->publishParticipantUpdate($room->getRoomKey(), $participantData, 'joined');

            return $this->redirectToRoute('app_room_show', [
                'roomKey' => $room->getRoomKey(),
            ]);
        }
        return $this->redirectToRoute('app_home_index');
    }

    #[Route('join', name: 'join')]
    public function join(Request $request, RoomRepository $roomRepository, EntityManagerInterface $entityManager, SessionInterface $session, MercurePublisher $mercurePublisher): Response
    {

        $roomKey = $request->get('room')['roomKey'];
        $existingRoom = $roomRepository->findOneBy(['roomKey' => $roomKey]);

        if (!$existingRoom) {
            $this->addFlash('error', 'Room not found!');
            return $this->redirectToRoute('app_home_index');
        }

        $form = $this->createForm(RoomType::class, $existingRoom);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $username = $request->get('room')['username'];
            // Create a simple user
            $user = new User();
            $user->setUsername($username);

            // Add user to room
            $existingRoom->addUser($user);

            $entityManager->persist($user);
            $entityManager->flush();

            // Store userLoginId in session for "login"
            $session->set('user_login_id', $user->getUserLoginId());

            // Publish participant update for user joining
            $participantData = [
                'id' => $user->getUserLoginId(),
                'username' => $user->getUsername(),
                'isCreator' => $existingRoom->getCreatedBy() === $user,
                'vote' => null,
            ];
            $mercurePublisher->publishParticipantUpdate($existingRoom->getRoomKey(), $participantData, 'joined');

            return $this->redirectToRoute('app_room_show', [
                'roomKey' => $existingRoom->getRoomKey(),
            ]);
        }

        // If form is not valid, add error message for debugging
        if ($form->isSubmitted()) {
            $this->addFlash('error', 'Please fill in all required fields.');
        }

        return $this->redirectToRoute('app_home_index');
    }

    #[Route('{roomKey}', name: 'show')]
    public function show($roomKey, Request $request, RoomRepository $roomRepository, UserRepository $userRepository, SessionInterface $session, EntityManagerInterface $entityManager, MercurePublisher $mercurePublisher): Response
    {
        $room = $roomRepository->findOneBy(['roomKey' => $roomKey]);

        if (!$room) {
            $this->addFlash('warning', 'Room not found!');
            return $this->redirectToRoute('app_home_index');
        }



        // Check if user is logged in via session
        $userLoginId = $session->get('user_login_id');
        if ($userLoginId) {
            $user = $userRepository->findByUserLoginId($userLoginId);
            if ($user) {
                // Check if user is a participant in the room
                if (!$room->getUsers()->contains($user)) {
                    // User is not in the room, add them back
                    $room->addUser($user);
                    $entityManager->flush();

                    // Publish participant update for user rejoining
                    $participantData = [
                        'id' => $user->getUserLoginId(),
                        'username' => $user->getUsername(),
                        'isCreator' => $room->getCreatedBy() === $user,
                        'vote' => null,
                    ];
                    $mercurePublisher->publishParticipantUpdate($room->getRoomKey(), $participantData, 'joined');
                }

                // User is logged in and is a participant, show the room
                return $this->render('room/index.html.twig', [
                    'room' => $room,
                    'user' => $user,
                    'mercureUrl' => $_ENV['MERCURE_PUBLIC_URL'] ?? 'http://127.0.0.1:3000/.well-known/mercure',
                ]);
            }
        }

        return $this->redirectToRoute('app_home_index', ['roomKey' => $roomKey]);

        // User not logged in, show join form for this specific room
        $joinRoom = new Room();
        $joinRoom->setRoomKey($roomKey);
        $joinForm = $this->createForm(RoomType::class, $joinRoom, [
            'action' => $this->generateUrl('app_room_join'),
        ]);

        return $this->render('room/join_prompt.html.twig', [
            'room' => $room,
            'joinForm' => $joinForm->createView(),
        ]);
    }

}
