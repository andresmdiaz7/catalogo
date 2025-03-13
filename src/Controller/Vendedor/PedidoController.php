<?php

namespace App\Controller\Vendedor;

use App\Entity\Pedido;
use App\Entity\PedidoDetalle;
use App\Service\CartService;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use App\Form\PedidoFilterType;
use Knp\Component\Pager\PaginatorInterface;

/**
 * Controlador para gestionar los pedidos de los clientes del vendedor
 */
#[Route('/vendedor/pedido')]
#[IsGranted('ROLE_VENDEDOR')]
class PedidoController extends AbstractController
{
    
    /**
     * Muestra un listado de los pedidos de los clientes del vendedor
     *
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/', name: 'app_vendedor_clientes_pedido_index')]
    public function index(Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response  
    {
        $vendedor = $this->getUser();
        
        $filterForm = $this->createForm(PedidoFilterType::class, null, [
            'vendedor' => $vendedor,
        ]);
        $filterForm->handleRequest($request);

        $queryBuilder = $entityManager->createQueryBuilder()
            ->select('p')
            ->from('App\Entity\Pedido', 'p')
            ->join('p.cliente', 'c')
            ->where('c.vendedor = :vendedor')
            ->setParameter('vendedor', $vendedor)
            ->orderBy('p.fecha', 'DESC');

        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $filters = $filterForm->getData();
            
            if (!empty($filters['cliente'])) {
                $queryBuilder
                    ->andWhere('p.cliente = :cliente')
                    ->setParameter('cliente', $filters['cliente']);
            }
            
            if (!empty($filters['nombreCliente'])) {
                $queryBuilder
                    ->andWhere('c.razonSocial LIKE :nombreCliente OR c.nombre LIKE :nombreCliente')
                    ->setParameter('nombreCliente', '%' . $filters['nombreCliente'] . '%');
            }
            
            if (!empty($filters['estado'])) {
                $queryBuilder
                    ->andWhere('p.estado = :estado')
                    ->setParameter('estado', $filters['estado']);
            }
            
            if (!empty($filters['fechaDesde'])) {
                $queryBuilder
                    ->andWhere('p.fecha >= :fechaDesde')
                    ->setParameter('fechaDesde', $filters['fechaDesde']);
            }
            
            if (!empty($filters['fechaHasta'])) {
                $queryBuilder
                    ->andWhere('p.fecha <= :fechaHasta')
                    ->setParameter('fechaHasta', $filters['fechaHasta']);
            }
        }

        $pedidos = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            10 // número de items por página
        );

        return $this->render('vendedor/pedido/index.html.twig', [
            'pedidos' => $pedidos,
            'filterForm' => $filterForm->createView(),
        ]);
    }

    /**
     * Muestra los detalles de un pedido de un cliente del vendedor
     *
     * @param Pedido $pedido
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/{id}', name: 'app_vendedor_clientes_pedido_show')]
    public function show(Pedido $pedido, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\Vendedor $vendedor */
        $vendedor = $this->getUser();
        
        // Verificar que el cliente del pedido esté asignado a este vendedor
        $cliente = $pedido->getCliente();
        
        if ($cliente->getVendedor() !== $vendedor) {
            throw $this->createAccessDeniedException('No tiene permiso para ver este pedido. El cliente no está asignado a su cartera.');
        }

        return $this->render('vendedor/pedido/show.html.twig', [
            'pedido' => $pedido,
        ]);
    }
    
}
