<?php

namespace App\Controller\Cliente;

use App\Entity\Articulo;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

#[Route('/cliente/cart')]
class CartController extends AbstractController
{
    public function __construct(
        private CartService $cartService,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/add', name: 'app_cliente_cart_add')]
    public function add(Request $request): Response
    {
        $codigo = $request->request->get('codigo');
        $cantidad = $request->request->get('cantidad', 1); // Por defecto, la cantidad es 1 si no se proporciona

        $articulo = $this->entityManager->getRepository(Articulo::class)->find($codigo);
        
        if (!$articulo) {
            throw $this->createNotFoundException('El artículo no existe.');
        }

        if (!$articulo->isHabilitadoWeb() || !$articulo->isHabilitadoGestion()) {
            throw $this->createNotFoundException('El artículo no está habilitado para la web o gestión.');
        }

        $this->cartService->add($articulo, $cantidad);
        
        $this->addFlash('success', 'Se agregaron '.$cantidad.' unidades de '.$articulo->getDetalle().' al carrito');
        
        $referer = $request->headers->get('referer');
        return $this->redirect($referer ?: $this->generateUrl('app_catalogo_index'));
    }

    #[Route('/', name: 'app_cliente_cart_index')]
    public function index(): Response
    {
        return $this->render('cliente/cart/index.html.twig', [
            'items' => $this->cartService->getItems(),
            'total' => $this->cartService->getTotal(),
        ]);
    }

    #[Route('/remove/{codigo}', name: 'app_cliente_cart_remove')]
    public function remove(int $codigo): Response
    {
        $articulo = $this->entityManager->getRepository(Articulo::class)->find($codigo);
        
        if (!$articulo) {
            throw $this->createNotFoundException('El artículo no existe.');
        }

        $this->cartService->remove($articulo);
        
        $this->addFlash('success', 'Artículo eliminado del carrito');
        
        return $this->redirectToRoute('app_cliente_cart_index');
    }

    #[Route('/clear', name: 'app_cliente_cart_clear')]
    public function clear(): Response
    {
        $this->cartService->clear();
        
        $this->addFlash('success', 'Carrito vaciado correctamente');
        
        return $this->redirectToRoute('app_cliente_cart_index');
    }

    #[Route('/increase/{codigo}', name: 'app_cliente_cart_increase')]
    public function increase(string $codigo, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);
        
        if (isset($cart[$codigo])) {
            $cart[$codigo]['cantidad']++;
            $session->set('cart', $cart);
        }
        
        return $this->redirectToRoute('app_cliente_cart_index');
    }

    #[Route('/decrease/{codigo}', name: 'app_cliente_cart_decrease')]
    public function decrease(string $codigo, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);
        
        if (isset($cart[$codigo])) {
            if ($cart[$codigo]['cantidad'] > 1) {
                $cart[$codigo]['cantidad']--;
            } else {
                unset($cart[$codigo]);
            }
            $session->set('cart', $cart);
        }
        
        return $this->redirectToRoute('app_cliente_cart_index');
    }
}