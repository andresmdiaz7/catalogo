<?php

namespace App\Controller\Cliente;

use App\Repository\PedidoRepository;
use App\Service\ClienteMssqlService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Form\Cliente\PerfilType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Service\ClienteManager;

#[Route('/cliente')]
#[IsGranted('ROLE_CLIENTE')]
class PanelController extends AbstractController
{
    private $clienteManager;
    
    // Inyectar ClienteManager en el constructor
    public function __construct(ClienteManager $clienteManager)
    {
        $this->clienteManager = $clienteManager;
    }

    #[Route('/', name: 'app_cliente_panel')]
    public function index(
        PedidoRepository $pedidoRepository,
        ClienteMssqlService $clienteMssqlService
    ): Response {
        /** @var Usuario $user */
        $user = $this->getUser();
        
        // Verificar si hay un cliente activo
        if (!$this->clienteManager->hasClienteActivo()) {
            // Intentar configurar automáticamente
            if (!$this->clienteManager->configurarClienteActivoAutomaticamente()) {
                return $this->redirectToRoute('app_cliente_seleccionar');
            }
        }
        
        // Verificar que el cliente activo pertenece al usuario actual
        if (!$this->clienteManager->validarClienteActivo()) {
            $this->addFlash('error', 'El cliente seleccionado no es válido');
            return $this->redirectToRoute('app_cliente_seleccionar');
        }
        
        $clienteActivo = $this->clienteManager->getClienteActivo();
        
        // Obtener el código del cliente para consultar en MSSQL
        $codigoCliente = $clienteActivo->getCodigo();
        
        // Obtener la deuda de cuenta corriente desde MSSQL
        
        $deudaCuentaCorriente = $clienteMssqlService->getDeudaCuentaCorriente((string)$codigoCliente);
        
        return $this->render('cliente/panel/index.html.twig', [
            'cliente' => $clienteActivo,
            'pedidos_recientes' => $pedidoRepository->findBy(
                ['cliente' => $clienteActivo],
                ['fecha' => 'DESC'],
                5
            ),
            'deuda_cuenta_corriente' => $deudaCuentaCorriente
        ]);
    }
} 