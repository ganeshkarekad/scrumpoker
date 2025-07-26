<?php

namespace App\Controller;

use App\Entity\Room;
use App\Form\RoomType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('', name: 'app_home_')]
final class HomeController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(): Response
    {
        $room = new Room();

        $form = $this->createForm(RoomType::class, $room, [
            'action' => $this->generateUrl('app_room_join'),
        ]);

        return $this->render('home/index.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
