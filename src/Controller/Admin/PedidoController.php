<?php

namespace App\Controller\Admin;

use App\Entity\Pedido;
use App\Form\PedidoType;
use App\Repository\PedidoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/pedidos')]
class PedidoController extends AdminController
{
    #[Route('/', name: 'app_admin_pedido_index')]
    public function index(PedidoRepository $pedidoRepository): Response
    {
        return $this->render('admin/pedido/index.html.twig', [
            'pedidos' => $pedidoRepository->findBy([], ['fecha' => 'DESC'])
        ]);
    }

    #[Route('/nuevo', name: 'app_admin_pedido_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $pedido = new Pedido();
        $form = $this->createForm(PedidoType::class, $pedido);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pedido->recalcularTotal();
            $entityManager->persist($pedido);
            $entityManager->flush();

            $this->addSuccessFlash('Pedido creado correctamente.');
            return $this->redirectToRoute('app_admin_pedido_index');
        }

        return $this->render('admin/pedido/new.html.twig', [
            'pedido' => $pedido,
            'form' => $form
        ]);
    }

    #[Route('/{id}/editar', name: 'app_admin_pedido_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        Pedido $pedido, 
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(PedidoType::class, $pedido);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pedido->recalcularTotal();
            $entityManager->flush();

            $this->addSuccessFlash('Pedido actualizado correctamente.');
            return $this->redirectToRoute('app_admin_pedido_index');
        }

        return $this->render('admin/pedido/edit.html.twig', [
            'pedido' => $pedido,
            'form' => $form
        ]);
    }

    #[Route('/{id}', name: 'app_admin_pedido_delete', methods: ['POST'])]
    public function delete(
        Request $request, 
        Pedido $pedido, 
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$pedido->getId(), $request->request->get('_token'))) {
            try {
                $entityManager->remove($pedido);
                $entityManager->flush();
                $this->addSuccessFlash('Pedido eliminado correctamente.');
            } catch (\Exception $e) {
                $this->addErrorFlash('No se puede eliminar el pedido.'. $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_admin_pedido_index');
    }

    #[Route('/{id}/cambiar-estado/{estado}', name: 'app_admin_pedido_cambiar_estado', methods: ['POST'])]
    public function cambiarEstado(
        Request $request,
        Pedido $pedido,
        string $estado,
        EntityManagerInterface $entityManager
    ): Response {
        
        if ($this->isCsrfTokenValid('cambiar-estado-'.$pedido->getId(), $request->request->get('_token'))) {
            $pedido->setEstado($estado);
            $pedido->setFechaLeido(new \DateTime());
            $entityManager->flush();
            $this->addSuccessFlash('Estado del pedido actualizado correctamente.');
            
        }
        
        return $this->redirectToRoute('app_admin_pedido_index');
    }

    #[Route('/{id}', name: 'app_admin_pedido_show')]
    public function show(Pedido $pedido): Response
    {
        return $this->render('admin/pedido/show.html.twig', [
            'pedido' => $pedido
        ]);
    }
} 