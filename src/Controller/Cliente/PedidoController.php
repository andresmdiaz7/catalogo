<?php

namespace App\Controller\Cliente;

use App\Entity\Pedido;
use App\Entity\PedidoDetalle;
use App\Entity\EstadoPedido;
use App\Services\CartManager;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Knp\Component\Pager\PaginatorInterface;
use App\Service\ClienteManager;
use App\Service\CarritoManager;
use App\Repository\PedidoRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;


/**
 * Controlador para gestionar los pedidos de los clientes
 */
#[Route('/cliente/pedido')]
#[IsGranted('ROLE_CLIENTE')]
class PedidoController extends AbstractController
{
    private $clienteManager;
    private $carritoManager;
    private $pedidoRepository;
    private $entityManager;

    public function __construct(
        ClienteManager $clienteManager,
        CarritoManager $carritoManager,
        PedidoRepository $pedidoRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->clienteManager = $clienteManager;
        $this->carritoManager = $carritoManager;
        $this->pedidoRepository = $pedidoRepository;
        $this->entityManager = $entityManager;
    }

    
    /**
     * Muestra un listado de los pedidos del cliente en la sesion actual
     *
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @return Response
     */
    #[Route('/', name: 'app_cliente_pedido_index')]
    public function index(
        EntityManagerInterface $entityManager, 
        PaginatorInterface $paginator, 
        Request $request
    ): Response {
        
        $cliente = $this->clienteManager->getClienteActivo();
        
        // Crear la consulta
        $query = $entityManager->getRepository(Pedido::class)
            ->createQueryBuilder('p')
            ->where('p.cliente = :cliente')
            ->setParameter('cliente', $cliente)
            ->orderBy('p.fecha', 'DESC')
            ->getQuery();
        
        // Paginar los resultados
        $pedidos = $paginator->paginate(
            $query            ,                 // Consulta a paginar
            $request->query->getInt('page', 1), // Número de página, 1 por defecto
            10                                  // Elementos por página
        );
        
        return $this->render('cliente/pedido/index.html.twig', [
            'pedidos' => $pedidos,
        ]);
    }

    /**
     * Muestra los detalles de un pedido del cliente en la sesion actual
     *
     * @param Pedido $pedido
     * @return Response
     */
    #[Route('/show/{id}', name: 'app_cliente_pedido_show', methods: ['GET'])]
    public function show(Request $request, Pedido $pedido): Response
    {
        try {
            
            if (!$pedido) {
                throw $this->createNotFoundException('El pedido solicitado no existe');
            }
            
            // Verificamos que el pedido pertenezca al cliente activo
            $clienteActivo = $this->carritoManager->getClienteManager()->getClienteActivo();
            
            
            if ($pedido->getCliente() !== $clienteActivo) {
                throw $this->createAccessDeniedException('No tienes permiso para ver este pedido');
            }
            
            return $this->render('cliente/pedido/show.html.twig', [
                'pedido' => $pedido
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error: ' . $e->getMessage());
            return $this->redirectToRoute('app_cliente_pedido_index');
        }
    }

    /**
     * Elimina un pedido del cliente en la sesion actual
     *
     * @param Request $request
     * @param Pedido $pedido
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/{id}/eliminar', name: 'app_cliente_pedido_delete', methods: ['POST'])]
    public function delete(Request $request, Pedido $pedido, EntityManagerInterface $entityManager): Response
    {
        // Verificar que el pedido pertenezca al cliente activo de la sesión
        $clienteActivo = $this->clienteManager->getClienteActivo();
        if ($pedido->getCliente() !== $clienteActivo) {
            throw $this->createAccessDeniedException('No tiene permiso para eliminar este pedido.');
        }

        // Verificar que el pedido no esté enviado
        if ($pedido->getEstado() === EstadoPedido::ENVIADO) {
            $this->addFlash('error', 'No se puede eliminar un pedido que ya ha sido enviado.');
            return $this->redirectToRoute('app_cliente_pedido_index');
        }
        
        if ($this->isCsrfTokenValid('delete'.$pedido->getId(), $request->request->get('_token'))) {
            try {
                $entityManager->remove($pedido);
                $entityManager->flush();
                $this->addFlash('success', 'Pedido eliminado correctamente.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Ocurrió un error al intentar eliminar el pedido.');
            }
        }

        return $this->redirectToRoute('app_cliente_pedido_index');
    }

    /**
     * Confirma el pedido del cliente en la sesion actual
     *
     * @param Request $request
     * @return Response
     */
    #[Route('/confirmar', name: 'app_cliente_pedido_confirmar', methods: ['GET', 'POST'])]
    public function confirmar(Request $request): Response
    {
        try {
            $carrito = $this->carritoManager->obtenerCarritoActivo();
            
            if (!$carrito || $carrito->getItems()->isEmpty()) {
                $this->addFlash('error', 'No hay artículos en el carrito');
                return $this->redirectToRoute('app_cliente_carrito_index');
            }
            
            // Si es una solicitud POST, crear el pedido
            if ($request->isMethod('POST')) {
                try {
                    $pedido = $this->carritoManager->convertirAPedido();

                    if ($pedido && $pedido->getId()) {
                        $this->addFlash('success', 'Pedido confirmado correctamente');
                        return $this->redirectToRoute('app_cliente_pedido_show', [
                            'id' => $pedido->getId()
                        ]);
                    } else {
                        $this->addFlash('error', 'No se pudo crear el pedido');
                    }
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Error al crear el pedido: ' . $e->getMessage());
                }
            }
            
            return $this->redirectToRoute('app_cliente_carrito_index');

        } catch (\Exception $e) {
            $this->addFlash('error', 'Error: ' . $e->getMessage());
            return $this->redirectToRoute('app_cliente_carrito_index');
        }
    }
}
