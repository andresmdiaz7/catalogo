<?php

namespace App\Controller\Admin;

use App\Entity\Pedido;
use App\Entity\EstadoPedido;
use App\Repository\ArticuloRepository;
use App\Repository\PedidoRepository;
use App\Repository\ClienteRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controlador para el panel principal de administración.
 * 
 * Este controlador maneja la vista principal del área de administración,
 * mostrando estadísticas generales del sistema como:
 * - Total de artículos habilitados
 * - Total de pedidos
 * - Pedidos pendientes
 * - Total de clientes
 * - Últimos pedidos
 * - Artículos destacados
 * 
 * @package App\Controller\Admin
 * @see AdminController
 */

#[Route('/admin')]
class PanelController extends AdminController
{
    #[Route('/panel', name: 'app_admin_panel')]
    public function panel(
        ArticuloRepository $articuloRepository,
        PedidoRepository $pedidoRepository,
        ClienteRepository $clienteRepository
    ): Response {
        // Estadísticas generales
        $stats = [
            'totalArticulos' => $articuloRepository->count(['habilitadoWeb' => true]),
            'totalPedidos' => $pedidoRepository->count([]),
            'pedidosPendientes' => $pedidoRepository->count(['estado' => EstadoPedido::PENDIENTE]),
            'totalClientes' => $clienteRepository->count([])
        ];

        // Obtener cantidad de artículos sin imagen
        $articulosSinImagenQb = $articuloRepository->createQueryBuilderArticulosSinImagen();
        $stats['articulosSinImagen'] = $articulosSinImagenQb->select('COUNT(a.codigo)')
            ->getQuery()
            ->getSingleScalarResult();

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