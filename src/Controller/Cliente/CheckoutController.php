<?php

namespace App\Controller\Cliente;

use App\Entity\Pedido;
use App\Entity\PedidoDetalle;
use App\Service\CartService;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cliente/checkout')]
#[IsGranted('ROLE_CLIENTE')]
class CheckoutController extends AbstractController
{
    public function __construct(
        private CartService $cartService,
        private EntityManagerInterface $entityManager,
        private EmailService $emailService
    ) {}

    #[Route('', name: 'app_cliente_checkout')]
    public function index(): Response
    {
        $cart = $this->cartService->getItems();
        
        if (empty($cart)) {
            $this->addFlash('warning', 'El carrito está vacío');
            return $this->redirectToRoute('app_cliente_cart_index');
        }

        return $this->render('cliente/checkout/index.html.twig', [
            'cart' => $cart,
            'total' => $this->cartService->getTotal()
        ]);
    }

    #[Route('/confirmar', name: 'app_cliente_checkout_confirm', methods: ['POST'])]
    public function confirm(Request $request): Response
    {
        $cart = $this->cartService->getCart();
        if (empty($cart)) {
            $this->addFlash('warning', 'El carrito está vacío');
            return $this->redirectToRoute('app_cliente_cart_index');
        }

        /** @var \App\Entity\Cliente $cliente */
        $cliente = $this->getUser();

        $pedido = new Pedido();
        $pedido->setCliente($cliente);
        $pedido->setFecha(new \DateTime());
        $pedido->setObservaciones($request->request->get('observaciones'));
        $pedido->setEnviado(false);

        foreach ($cart as $item) {
            $detalle = new PedidoDetalle();
            $detalle->setPedido($pedido);
            $detalle->setArticulo($item['articulo']);
            $detalle->setCantidad($item['cantidad']);
            $detalle->setPrecio($item['precio']);
            $pedido->addDetalle($detalle);
        }

        $this->entityManager->persist($pedido);
        $this->entityManager->flush();

        // Enviar emails
        $this->emailService->sendPedidoConfirmation($pedido);
        $this->emailService->sendPedidoNotification($pedido);

        // Limpiar el carrito después de crear el pedido
        $this->cartService->clear();

        $this->addFlash('success', 'Pedido realizado correctamente');
        return $this->redirectToRoute('app_cliente_pedido_show', ['id' => $pedido->getId()]);
    }
} 