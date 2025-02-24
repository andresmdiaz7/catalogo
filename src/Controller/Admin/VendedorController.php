<?php

namespace App\Controller\Admin;

use App\Entity\Vendedor;
use App\Form\VendedorType;
use App\Repository\VendedorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/vendedores')]
class VendedorController extends AbstractController
{
    #[Route('/', name: 'app_admin_vendedor_index', methods: ['GET'])]
    public function index(VendedorRepository $vendedorRepository): Response
    {
        return $this->render('admin/vendedor/index.html.twig', [
            'vendedores' => $vendedorRepository->findAll(),
        ]);
    }

    #[Route('/nuevo', name: 'app_admin_vendedor_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $vendedor = new Vendedor();
        $form = $this->createForm(VendedorType::class, $vendedor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($vendedor);
            $entityManager->flush();

            $this->addFlash('success', 'Vendedor creado correctamente');
            return $this->redirectToRoute('app_admin_vendedor_index');
        }

        return $this->render('admin/vendedor/new.html.twig', [
            'vendedor' => $vendedor,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/editar', name: 'app_admin_vendedor_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Vendedor $vendedor, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(VendedorType::class, $vendedor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Vendedor actualizado correctamente');
            return $this->redirectToRoute('app_admin_vendedor_index');
        }

        return $this->render('admin/vendedor/edit.html.twig', [
            'vendedor' => $vendedor,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_vendedor_delete', methods: ['POST'])]
    public function delete(Request $request, Vendedor $vendedor, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$vendedor->getId(), $request->request->get('_token'))) {
            $entityManager->remove($vendedor);
            $entityManager->flush();
            $this->addFlash('success', 'Vendedor eliminado correctamente');
        }

        return $this->redirectToRoute('app_admin_vendedor_index');
    }

    #[Route('/{id}/toggle-activo', name: 'app_admin_vendedor_toggle_activo', methods: ['POST'])]
    public function toggleActivo(
        Request $request, 
        Vendedor $vendedor, 
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('toggle-activo'.$vendedor->getId(), $request->request->get('_token'))) {
            $vendedor->setActivo(!$vendedor->isActivo());
            $entityManager->flush();

            $this->addSuccessFlash(
                'El vendedor ha sido ' . ($vendedor->isActivo() ? 'activado' : 'desactivado')
            );
        }

        return $this->redirectToRoute('app_admin_vendedor_index');
    }
} 