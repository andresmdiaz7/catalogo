<?php

namespace App\Controller\Cliente;

use App\Repository\PedidoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Form\Cliente\PerfilType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

#[Route('/cliente')]
#[IsGranted('ROLE_CLIENTE')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_cliente_dashboard')]
    public function index(PedidoRepository $pedidoRepository): Response
    {
        /** @var \App\Entity\Cliente $cliente */
        $cliente = $this->getUser();
        
        return $this->render('cliente/dashboard/index.html.twig', [
            'pedidos_recientes' => $pedidoRepository->findBy(
                ['cliente' => $cliente],
                ['fecha' => 'DESC'],
                5
            ),
        ]);
    }

} 