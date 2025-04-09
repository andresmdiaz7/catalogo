<?php

namespace App\Controller\Admin;

use App\Entity\SliderUbicacion;
use App\Form\SliderUbicacionType;
use App\Repository\SliderUbicacionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/slider-ubicacion')]
#[IsGranted('ROLE_ADMIN')]
class SliderUbicacionController extends AbstractController
{
    #[Route('/', name: 'app_admin_slider_ubicacion_index', methods: ['GET'])]
    public function index(SliderUbicacionRepository $sliderUbicacionRepository): Response
    {
        return $this->render('admin/slider_ubicacion/index.html.twig', [
            'slider_ubicaciones' => $sliderUbicacionRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_slider_ubicacion_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $sliderUbicacion = new SliderUbicacion();
        $form = $this->createForm(SliderUbicacionType::class, $sliderUbicacion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($sliderUbicacion);
            $entityManager->flush();

            $this->addFlash('success', 'Ubicación de slider creada correctamente.');
            return $this->redirectToRoute('app_admin_slider_ubicacion_index');
        }

        return $this->render('admin/slider_ubicacion/new.html.twig', [
            'slider_ubicacion' => $sliderUbicacion,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_slider_ubicacion_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SliderUbicacion $sliderUbicacion, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SliderUbicacionType::class, $sliderUbicacion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Ubicación de slider actualizada correctamente.');
            return $this->redirectToRoute('app_admin_slider_ubicacion_index');
        }

        return $this->render('admin/slider_ubicacion/edit.html.twig', [
            'slider_ubicacion' => $sliderUbicacion,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_slider_ubicacion_delete', methods: ['POST'])]
    public function delete(Request $request, SliderUbicacion $sliderUbicacion, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$sliderUbicacion->getId(), $request->request->get('_token'))) {
            $entityManager->remove($sliderUbicacion);
            $entityManager->flush();
            $this->addFlash('success', 'Ubicación de slider eliminada correctamente.');
        }

        return $this->redirectToRoute('app_admin_slider_ubicacion_index');
    }
}
