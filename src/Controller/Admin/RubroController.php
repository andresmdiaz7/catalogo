<?php

namespace App\Controller\Admin;

use App\Entity\Rubro;
use App\Form\RubroType;
use App\Repository\RubroRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controlador para la gestión de rubros.
 * 
 * Este controlador maneja la organización de los rubros del catálogo:
 * - Listado de rubros
 * - Creación de nuevos rubros
 * - Edición de rubros existentes
 * - Eliminación de rubros
 * - Asignación de rubros a secciones
 * - Gestión de subrubros por rubro
 * 
 * @package App\Controller\Admin
 * @see AdminController
 */
#[Route('/admin/rubros')]
class RubroController extends AdminController
{
    #[Route('/', name: 'app_admin_rubro_index')]
    public function index(RubroRepository $rubroRepository): Response
    {
        return $this->render('admin/rubro/index.html.twig', [
            'rubros' => $rubroRepository->findAll()
        ]);
    }

    #[Route('/nuevo', name: 'app_admin_rubro_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $rubro = new Rubro();
        $form = $this->createForm(RubroType::class, $rubro);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($rubro);
            $entityManager->flush();

            $this->addSuccessFlash('Rubro creado correctamente.');
            return $this->redirectToRoute('app_admin_rubro_index');
        }

        return $this->render('admin/rubro/new.html.twig', [
            'rubro' => $rubro,
            'form' => $form
        ]);
    }

    #[Route('/{id}/editar', name: 'app_admin_rubro_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Rubro $rubro, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RubroType::class, $rubro);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addSuccessFlash('Rubro actualizado correctamente.');
            return $this->redirectToRoute('app_admin_rubro_index');
        }

        return $this->render('admin/rubro/edit.html.twig', [
            'rubro' => $rubro,
            'form' => $form
        ]);
    }

    #[Route('/{id}', name: 'app_admin_rubro_delete', methods: ['POST'])]
    public function delete(
        Request $request, 
        Rubro $rubro, 
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$rubro->getId(), $request->request->get('_token'))) {
            try {
                $entityManager->remove($rubro);
                $entityManager->flush();
                $this->addSuccessFlash('Rubro eliminado correctamente.');
            } catch (\Exception $e) {
                $this->addErrorFlash('No se puede eliminar el rubro porque tiene subrubros asociados.');
            }
        }

        return $this->redirectToRoute('app_admin_rubro_index');
    }
} 