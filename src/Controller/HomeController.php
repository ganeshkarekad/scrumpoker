<?php

namespace App\Controller;

use App\Entity\Room;
use App\Entity\User;
use App\Form\RoomType;
use App\Form\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('', name: 'app_home_')]
final class HomeController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(Request $request): Response
    {
        $room = new Room();
        $user = new User();

        // Check if roomKey is provided as query parameter (from redirect)
        $roomKey = $request->query->get('roomKey');
        if ($roomKey) {
            $room->setRoomKey($roomKey);
        }

        $form = $this->createForm(RoomType::class, $room, [
            'action' => $this->generateUrl('app_room_join'),
        ]);

        $userForm = $this->createForm(UserType::class, $user, [
            'action' => $this->generateUrl('app_room_create')
        ]);

        return $this->render('home/index.html.twig', [
            'form' => $form->createView(),
            'userForm' => $userForm->createView(),
        ]);
    }

}
