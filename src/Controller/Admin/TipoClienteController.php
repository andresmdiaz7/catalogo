<?php

namespace App\Controller\Admin;

use App\Entity\TipoCliente;
use App\Form\TipoClienteType;
use App\Repository\TipoClienteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/tipos-cliente')]
class TipoClienteController extends AdminController
{
    #[Route('/', name: 'app_admin_tipo_cliente_index')]
    public function index(TipoClienteRepository $tipoClienteRepository): Response
    {
        return $this->render('admin/tipo_cliente/index.html.twig', [
            'tipos_cliente' => $tipoClienteRepository->findBy([], ['nombre' => 'ASC'])
        ]);
    }

    #[Route('/nuevo', name: 'app_admin_tipo_cliente_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tipoCliente = new TipoCliente();
        $form = $this->createForm(TipoClienteType::class, $tipoCliente);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tipoCliente);
            $entityManager->flush();

            $this->addSuccessFlash('Tipo de cliente creado correctamente.');
            return $this->redirectToRoute('app_admin_tipo_cliente_index');
        }

        return $this->render('admin/tipo_cliente/new.html.twig', [
            'tipo_cliente' => $tipoCliente,
            'form' => $form
        ]);
    }

    #[Route('/{id}/editar', name: 'app_admin_tipo_cliente_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        TipoCliente $tipoCliente, 
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(TipoClienteType::class, $tipoCliente);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addSuccessFlash('Tipo de cliente actualizado correctamente.');
            return $this->redirectToRoute('app_admin_tipo_cliente_index');
        }

        return $this->render('admin/tipo_cliente/edit.html.twig', [
            'tipo_cliente' => $tipoCliente,
            'form' => $form
        ]);
    }

    #[Route('/{id}', name: 'app_admin_tipo_cliente_delete', methods: ['POST'])]
    public function delete(
        Request $request, 
        TipoCliente $tipoCliente, 
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$tipoCliente->getId(), $request->request->get('_token'))) {
            try {
                $entityManager->remove($tipoCliente);
                $entityManager->flush();
                $this->addSuccessFlash('Tipo de cliente eliminado correctamente.');
            } catch (\Exception $e) {
                $this->addErrorFlash('No se puede eliminar el tipo de cliente porque tiene clientes asociados.');
            }
        }

        return $this->redirectToRoute('app_admin_tipo_cliente_index');
    }
} 