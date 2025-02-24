<?php

namespace App\Controller\Admin;

use App\Entity\CategoriaImpositiva;
use App\Form\CategoriaImpositivaType;
use App\Repository\CategoriaImpositivaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/categorias-impositivas')]
class CategoriaImpositivaController extends AbstractController
{
    #[Route('/', name: 'app_admin_categoria_impositiva_index', methods: ['GET'])]
    public function index(CategoriaImpositivaRepository $categoriaImpositivaRepository): Response
    {
        return $this->render('admin/categoria_impositiva/index.html.twig', [
            'categorias' => $categoriaImpositivaRepository->findBy([], ['nombre' => 'ASC']),
        ]);
    }

    #[Route('/nueva', name: 'app_admin_categoria_impositiva_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $categoria = new CategoriaImpositiva();
        $form = $this->createForm(CategoriaImpositivaType::class, $categoria);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($categoria);
            $entityManager->flush();

            $this->addFlash('success', sprintf(
                'Categoría impositiva "%s" creada correctamente',
                $categoria->getNombre()
            ));
            return $this->redirectToRoute('app_admin_categoria_impositiva_index');
        }

        return $this->render('admin/categoria_impositiva/new.html.twig', [
            'categoria' => $categoria,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/editar', name: 'app_admin_categoria_impositiva_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CategoriaImpositiva $categoria, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategoriaImpositivaType::class, $categoria);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', sprintf(
                'Categoría impositiva "%s" actualizada correctamente',
                $categoria->getNombre()
            ));
            return $this->redirectToRoute('app_admin_categoria_impositiva_index');
        }

        return $this->render('admin/categoria_impositiva/edit.html.twig', [
            'categoria' => $categoria,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_categoria_impositiva_delete', methods: ['POST'])]
    public function delete(Request $request, CategoriaImpositiva $categoria, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$categoria->getId(), $request->request->get('_token'))) {
            $nombre = $categoria->getNombre();
            $entityManager->remove($categoria);
            $entityManager->flush();
            
            $this->addFlash('success', sprintf(
                'Categoría impositiva "%s" eliminada correctamente',
                $nombre
            ));
        }

        return $this->redirectToRoute('app_admin_categoria_impositiva_index');
    }
} 