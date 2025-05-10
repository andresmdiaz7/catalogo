<?php

namespace App\Controller\Vendedor;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\ClienteRepository;
use App\Repository\PedidoRepository;
use App\Repository\VendedorRepository;

#[Route('/vendedor')]
#[IsGranted('ROLE_VENDEDOR')]
class VendedorController extends AbstractController
{
    #[Route('/panel', name: 'app_vendedor_panel')]
    public function index(ClienteRepository $clienteRepository, PedidoRepository $pedidoRepository, VendedorRepository $vendedorRepository): Response
    {
        $user = $this->getUser();
        
        // Obtener el Vendedor asociado al Usuario
        $vendedor = $vendedorRepository->findOneBy(['usuario' => $user]);
        
        if (!$vendedor) {
            throw $this->createAccessDeniedException('No tienes acceso a esta sección');
        }
        
        $clientes = $clienteRepository->findBy(['vendedor' => $vendedor]);
        $pedidos = $pedidoRepository->findByVendedorClientes($vendedor);

        return $this->render('vendedor/panel.html.twig', [
            'clientes' => $clientes,
            'pedidos' => $pedidos,
        ]);
    }
}
