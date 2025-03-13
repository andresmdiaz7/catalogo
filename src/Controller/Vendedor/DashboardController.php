<?php

namespace App\Controller\Vendedor;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\ClienteRepository;
use App\Repository\PedidoRepository;

#[Route('/vendedor')]
#[IsGranted('ROLE_VENDEDOR')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_vendedor_dashboard')]
    public function index(ClienteRepository $clienteRepository, PedidoRepository $pedidoRepository): Response
    {
        $vendedor = $this->getUser();
        $clientes = $clienteRepository->findBy(['vendedor' => $vendedor]);
        $pedidos = $pedidoRepository->findByVendedorClientes($vendedor);

        return $this->render('vendedor/dashboard/index.html.twig', [
            'clientes' => $clientes,
            'pedidos' => $pedidos,
        ]);
    }
}
