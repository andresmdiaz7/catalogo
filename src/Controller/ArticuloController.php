<?php

namespace App\Controller;

use App\Entity\Articulo;
use App\Repository\ArticuloRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ArticuloController extends AbstractController
{
    #[Route('/articulo/{codigo}', name: 'app_articulo_show')]
    public function show(string $codigo, ArticuloRepository $articuloRepository): Response
    {
        $articulo = $articuloRepository->findOneBy(['codigo' => $codigo]);

        if (!$articulo) {
            throw $this->createNotFoundException('El artículo no existe.');
        }

        return $this->render('articulo/show.html.twig', [
            'articulo' => $articulo
        ]);
    }
} 