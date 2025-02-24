<?php

namespace App\Controller\Admin;

use App\Entity\Seccion;
use App\Form\SeccionType;
use App\Repository\SeccionRepository;
use App\Repository\RubroRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/seccion')]
class SeccionController extends AbstractController
{
    #[Route('/', name: 'app_admin_seccion_index', methods: ['GET'])]
    public function index(SeccionRepository $seccionRepository): Response
    {
        return $this->render('admin/seccion/index.html.twig', [
            'secciones' => $seccionRepository->findBy([], ['orden' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_admin_seccion_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $seccion = new Seccion();
        $form = $this->createForm(SeccionType::class, $seccion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($seccion);
            $entityManager->flush();

            $this->addFlash('success', 'Sección creada correctamente');
            return $this->redirectToRoute('app_admin_seccion_index');
        }

        return $this->render('admin/seccion/new.html.twig', [
            'seccion' => $seccion,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_seccion_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Seccion $seccion, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SeccionType::class, $seccion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Sección actualizada correctamente');
            return $this->redirectToRoute('app_admin_seccion_index');
        }

        return $this->render('admin/seccion/edit.html.twig', [
            'seccion' => $seccion,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_seccion_delete', methods: ['POST'])]
    public function delete(Request $request, Seccion $seccion, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$seccion->getId(), $request->request->get('_token'))) {
            try {
                $entityManager->remove($seccion);
                $entityManager->flush();
                $this->addFlash('success', 'Sección eliminada correctamente');
            } catch (\Exception $e) {
                $this->addFlash('error', 'No se puede eliminar la sección porque tiene artículos asociados');
            }
        }

        return $this->redirectToRoute('app_admin_seccion_index');
    }

    #[Route('/{id}/rubros', name: 'app_admin_seccion_rubros', methods: ['GET', 'POST'])]
    public function asignarRubros(
        Request $request, 
        Seccion $seccion, 
        RubroRepository $rubroRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if ($request->isMethod('POST')) {
            // Obtener los IDs de los rubros seleccionados
            $rubroIds = $request->request->all('rubros');
            
            // Limpiar rubros existentes
            foreach ($seccion->getRubros() as $rubro) {
                $rubro->setSeccion(null);
            }
            
            // Asignar nuevos rubros
            $rubrosSeleccionados = $rubroRepository->findBy(['codigo' => $rubroIds]);
            foreach ($rubrosSeleccionados as $rubro) {
                $rubro->setSeccion($seccion);
            }
            
            $entityManager->flush();
            $this->addFlash('success', 'Rubros asignados correctamente.');
            
            return $this->redirectToRoute('app_admin_seccion_rubros', ['id' => $seccion->getId()]);
        }

        // Obtener todos los rubros disponibles
        $rubrosDisponibles = $rubroRepository->findBy([], ['codigo' => 'ASC']);

        return $this->render('admin/seccion/rubros.html.twig', [
            'seccion' => $seccion,
            'rubros' => $rubrosDisponibles
        ]);
    }
} 