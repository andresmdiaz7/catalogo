<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PagesController extends AbstractController
{
    #[Route('/division-iluminacion', name: 'app_division_iluminacion')]
    public function divisionIluminacion(): Response
    {
        return $this->render('pages/division_iluminacion.html.twig', [
            'title' => 'División Iluminación',
        ]);
    }

    #[Route('/division-industria', name: 'app_division_industria')]
    public function divisionIndustria(): Response
    {
        return $this->render('pages/division_industria.html.twig', [
            'title' => 'División Industria',
        ]);
    }

    #[Route('/division-telecomunicaciones', name: 'app_division_telecomunicaciones')]
    public function divisionTelecomunicaciones(): Response
    {
        return $this->render('pages/division_telecomunicaciones.html.twig', [
            'title' => 'División Telecomunicaciones',
        ]);
    }
}
