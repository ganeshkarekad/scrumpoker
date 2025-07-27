<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/info/', name: 'app_info_')]
final class InfoController extends AbstractController
{
    #[Route('terms-and-conditions', name: 'terms')]
    public function terms(): Response
    {
        return $this->render('info/terms.html.twig');
    }
}
