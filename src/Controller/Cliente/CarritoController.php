<?php

namespace App\Controller\Cliente;

use App\Entity\Articulo;
use App\Service\CarritoManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Repository\ArticuloRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cliente/carrito')]
#[IsGranted('ROLE_CLIENTE')]
class CarritoController extends AbstractController
{
    private $carritoManager;
    private $articuloRepository;

    public function __construct(
        CarritoManager $carritoManager,
        ArticuloRepository $articuloRepository
    ) {
        $this->carritoManager = $carritoManager;
        $this->articuloRepository = $articuloRepository;
    }

    #[Route('/', name: 'app_cliente_carrito_index')]
    public function index(): Response
    {
        $carrito = $this->carritoManager->obtenerCarritoActivo();

        return $this->render('cliente/carrito/index.html.twig', [
            'carrito' => $carrito
        ]);
    }

    #[Route('/agregar', name: 'app_cliente_carrito_agregar', methods: ['POST'])]
    public function agregarArticulo(Request $request): Response
    {
        $codigo = $request->request->get('codigo');
        $articulo = $this->articuloRepository->find($codigo);
        if (!$articulo) {
            throw $this->createNotFoundException('Artículo no encontrado');
        }

        $cantidad = (int) $request->request->get('cantidad', 1);

        $this->carritoManager->agregarArticulo($articulo, $cantidad);

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => true]);
        }

        $this->addFlash('success', 'Artículo agregado al carrito');
        return $this->redirectToRoute('app_cliente_carrito_index');
    }


    /**
     * Elimina un item del carrito de compras
     * @param int $id ID del item a eliminar
     * @param Request $request Objeto Request
     * @return Response Respuesta HTTP
     */
    #[Route('/eliminar/{id}', name: 'app_cliente_carrito_eliminar', methods: ['POST'])]
    public function eliminarItem(int $id, Request $request): Response
    {
        $item = $this->carritoManager->getItem($id);
        
        if (!$item) {
            throw $this->createNotFoundException('Articulo no encontrado en el carrito');
        }

        $this->carritoManager->eliminarItem($item);

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => true]);
        }

        $this->addFlash('success', 'Articulo eliminado del carrito');
        return $this->redirectToRoute('app_cliente_carrito_index');
    }

    
    #[Route('/actualizar/{id}', name: 'app_cliente_carrito_actualizar', methods: ['POST'])]
    public function actualizarCantidad(int $id, Request $request): Response
    {
        $item = $this->carritoManager->getItem($id);
        if (!$item) {
            throw $this->createNotFoundException('Item no encontrado');
        }

        $cantidad = (int) $request->request->get('cantidad', 1);
        $this->carritoManager->actualizarCantidad($item, $cantidad);

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => true]);
        }

        $this->addFlash('success', 'Cantidad actualizada');
        return $this->redirectToRoute('app_cliente_carrito_index');
    }

    #[Route('/vaciar', name: 'app_cliente_carrito_vaciar', methods: ['POST'])]
    public function vaciarCarrito(Request $request): Response
    {
        $this->carritoManager->vaciarCarrito();

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => true]);
        }

        $this->addFlash('success', 'Carrito vaciado');
        return $this->redirectToRoute('app_cliente_carrito_index');
    }
}