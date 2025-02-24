<?php

namespace App\Controller\Admin;

use App\Entity\ResponsableLogistica;
use App\Form\ResponsableLogisticaType;
use App\Repository\ResponsableLogisticaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/responsables-logistica')]
class ResponsableLogisticaController extends AbstractController
{
    #[Route('/', name: 'app_admin_responsable_logistica_index', methods: ['GET'])]
    public function index(ResponsableLogisticaRepository $responsableLogisticaRepository): Response
    {
        return $this->render('admin/responsable_logistica/index.html.twig', [
            'responsables' => $responsableLogisticaRepository->findBy([], ['apellido' => 'ASC', 'nombre' => 'ASC']),
        ]);
    }

    #[Route('/nuevo', name: 'app_admin_responsable_logistica_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $responsable = new ResponsableLogistica();
        $form = $this->createForm(ResponsableLogisticaType::class, $responsable);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($responsable);
            $entityManager->flush();

            $this->addFlash('success', sprintf(
                'Responsable de logística %s %s creado correctamente',
                $responsable->getNombre(),
                $responsable->getApellido()
            ));
            return $this->redirectToRoute('app_admin_responsable_logistica_index');
        }

        return $this->render('admin/responsable_logistica/new.html.twig', [
            'responsable' => $responsable,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/editar', name: 'app_admin_responsable_logistica_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ResponsableLogistica $responsable, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ResponsableLogisticaType::class, $responsable);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', sprintf(
                'Responsable de logística %s %s actualizado correctamente',
                $responsable->getNombre(),
                $responsable->getApellido()
            ));
            return $this->redirectToRoute('app_admin_responsable_logistica_index');
        }

        return $this->render('admin/responsable_logistica/edit.html.twig', [
            'responsable' => $responsable,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_responsable_logistica_delete', methods: ['POST'])]
    public function delete(Request $request, ResponsableLogistica $responsable, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$responsable->getId(), $request->request->get('_token'))) {
            $nombreCompleto = sprintf('%s %s', $responsable->getNombre(), $responsable->getApellido());
            $entityManager->remove($responsable);
            $entityManager->flush();
            
            $this->addFlash('success', sprintf(
                'Responsable de logística %s eliminado correctamente',
                $nombreCompleto
            ));
        }

        return $this->redirectToRoute('app_admin_responsable_logistica_index');
    }
} 