<?php

namespace App\Controller;

use App\Form\RoomType;
use App\Repository\RoomRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/room/', name: 'app_room_')]
final class RoomController extends AbstractController
{
    #[Route('create', name: 'create')]
    public function create(Request $request): Response
    {
        $form = $this->createForm(RoomType::class);

        $form->handleRequest($request);

        dd($form);

    }


    #[Route('join', name: 'join')]
    public function join(Request $request): Response
    {
        $form = $this->createForm(RoomType::class);

        $roomKey = $request->get('room')['roomKey'];

        return $this->redirectToRoute('app_room_show', [
            'roomKey' => $roomKey,
        ]);
    }

    #[Route('{roomKey}', name: 'show')]
    public function show($roomKey, Request $request, RoomRepository $roomRepository): Response
    {
        $room = $roomRepository->findOneBy(['roomKey' => $roomKey]);

        if (!$room) {
            $this->addFlash('warning', 'Room not found!');

            return $this->redirectToRoute('app_home_index');
        }

        return $this->render('room/index.html.twig', [
            'controller_name' => 'RoomController',
        ]);
    }

}
