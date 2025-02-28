<?php

namespace App\Controller\Cliente;

use App\Entity\Pedido;
use App\Entity\PedidoDetalle;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador para gestionar los pedidos de los clientes
 */
#[Route('/cliente/pedidos')]
#[IsGranted('ROLE_CLIENTE')]
class PedidoController extends AbstractController
{
    /**
     * Crea un nuevo pedido con los items del carrito y luego limpia el carro
     *
     * @param CartService $cartService
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/create', name: 'app_cliente_pedido_create', methods: ['POST'])]
    public function create(
        CartService $cartService,
        EntityManagerInterface $entityManager
    ): Response {
        try {
            $items = $cartService->getItems();
            if (empty($items)) {
                $this->addFlash('error', 'El carrito está vacío');
                return $this->redirectToRoute('app_cliente_cart_index');
            }

            $pedido = new Pedido();
            $pedido->setCliente($this->getUser());
            $pedido->setFecha(new \DateTime());

            $totalPedido = 0;
            foreach ($items as $item) {
                $detalle = new PedidoDetalle();
                $detalle->setArticulo($entityManager->getReference('App\Entity\Articulo', $item['codigo']));
                $detalle->setCantidad($item['cantidad']);
                $detalle->setPrecioUnitario($item['precio']);
                $pedido->addDetalle($detalle);
                $entityManager->persist($detalle);
                $totalPedido += $item['precio'] * $item['cantidad'];
            }

            $pedido->setTotal($totalPedido);

            $entityManager->persist($pedido);
            $entityManager->flush();
            $cartService->clear();

            $this->addFlash('success', 'Pedido creado correctamente');
            return $this->redirectToRoute('app_cliente_pedidos');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Ocurrió un error al crear el pedido: ' . $e->getMessage());
            return $this->redirectToRoute('app_cliente_cart_index');
        }
    }
    /**
     * Muestra un listado de los pedidos del cliente actual
     *
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/', name: 'app_cliente_pedidos')]
    public function index(EntityManagerInterface $entityManager): Response  
    {
        /** @var \App\Entity\Cliente $cliente */
        $cliente = $this->getUser();
        
        return $this->render('cliente/pedido/index.html.twig', [
            'pedidos' => $entityManager->getRepository(Pedido::class)->findBy(
                ['cliente' => $cliente],
                ['fecha' => 'DESC']
            ),
        ]);
    }

    /**
     * Muestra los detalles de un pedido
     *
     * @param Pedido $pedido
     * @return Response
     */
    #[Route('/{id}', name: 'app_cliente_pedido_show')]
    public function show(Pedido $pedido): Response
    {
        // Verificar que el pedido pertenezca al cliente actual
        if ($pedido->getCliente() !== $this->getUser()) {
            throw $this->createAccessDeniedException('No tiene permiso para ver este pedido.');
        }

        return $this->render('cliente/pedido/show.html.twig', [
            'pedido' => $pedido,
        ]);
    }

    /**
     * Elimina un pedido
     *
     * @param Request $request
     * @param Pedido $pedido
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/{id}/eliminar', name: 'app_cliente_pedido_delete', methods: ['POST'])]
    public function delete(Request $request, Pedido $pedido, EntityManagerInterface $entityManager): Response
    {
        // Verificar que el pedido pertenezca al cliente actual
        if ($pedido->getCliente() !== $this->getUser()) {
            throw $this->createAccessDeniedException('No tiene permiso para eliminar este pedido.');
        }

        // Verificar que el pedido no esté enviado
        if ($pedido->getEstado() === 'enviado') {
            $this->addFlash('error', 'No se puede eliminar un pedido que ya ha sido enviado.');
            return $this->redirectToRoute('app_cliente_pedidos');
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

        return $this->redirectToRoute('app_cliente_pedidos');
    }
}