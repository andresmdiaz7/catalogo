<?php

namespace App\Controller\Cliente;

use App\Repository\PedidoRepository;
use App\Service\ClienteFacturacionService;
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
    public function index(
        PedidoRepository $pedidoRepository,
        ClienteFacturacionService $clienteFacturacionService
    ): Response {
        /** @var \App\Entity\Cliente $cliente */
        $cliente = $this->getUser();
        
        // Obtenemos el código de cliente en el sistema MSSQL
        // Si el ID en MSSQL es diferente, puedes tener un campo
        // en tu entidad Cliente que guarde la relación
        $idClienteMssql = $cliente->getCodigo(); // O un campo específico como $cliente->getIdMssql()
        
        
        // Obtenemos el total pendiente de la cuenta corriente
        $deudaCuentaCorriente = $clienteFacturacionService->getDeudaCuentaCorriente((int)$idClienteMssql);
        
        return $this->render('cliente/dashboard/index.html.twig', [
            'pedidos_recientes' => $pedidoRepository->findBy(
                ['cliente' => $cliente],
                ['fecha' => 'DESC'],
                5
            ),
            'deuda_cuenta_corriente' => $deudaCuentaCorriente
        ]);
    }

} 