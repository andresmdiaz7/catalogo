<?php

namespace App\Controller;

use App\Repository\ArticuloRepository;
use App\Repository\SeccionRepository;
use App\Repository\RubroRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        ArticuloRepository $articuloRepository,
        SeccionRepository $seccionRepository,
        RubroRepository $rubroRepository
    ): Response {
        return $this->render('home/index.html.twig', [
            'secciones' => $seccionRepository->findAll(),
            'rubros' => $rubroRepository->findAll(),
            'articulos' => $articuloRepository->findBy(
                ['habilitadoWeb' => true],
                ['destacado' => 'DESC', 'codigo' => 'ASC'],
                12
            ),
        ]);
    }

    #[Route('/buscar', name: 'app_buscar')]
    public function buscar(Request $request, ArticuloRepository $articuloRepository): Response
    {
        $query = $request->query->get('q');
        $articulos = $articuloRepository->buscar($query);

        return $this->render('home/buscar.html.twig', [
            'articulos' => $articulos,
            'query' => $query
        ]);
    }
} 