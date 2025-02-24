<?php

namespace App\Controller\Admin;

use App\Repository\ArticuloRepository;
use App\Repository\PedidoRepository;
use App\Repository\ClienteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_admin_dashboard')]
    public function index(
        ArticuloRepository $articuloRepository,
        PedidoRepository $pedidoRepository,
        ClienteRepository $clienteRepository
    ): Response {
        // Estadísticas generales
        $stats = [
            'totalArticulos' => $articuloRepository->count(['habilitadoWeb' => true]),
            'totalPedidos' => $pedidoRepository->count([]),
            'pedidosPendientes' => $pedidoRepository->count(['estado' => false]),
            'totalClientes' => $clienteRepository->count([])
        ];

        // Últimos pedidos
        $ultimosPedidos = $pedidoRepository->findBy(
            [],
            ['fecha' => 'DESC'],
            5
        );

        // Artículos destacados
        $articulosDestacados = $articuloRepository->findBy(
            ['destacado' => true, 'habilitadoWeb' => true],
            [],
            5
        );

        return $this->render('admin/dashboard/index.html.twig', [
            'stats' => $stats,
            'ultimosPedidos' => $ultimosPedidos,
            'articulosDestacados' => $articulosDestacados
        ]);
    }
} 