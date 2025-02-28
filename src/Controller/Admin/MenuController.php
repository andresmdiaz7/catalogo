<?php

namespace App\Controller\Admin;

use App\Entity\Menu;
use App\Entity\MenuSeccion;
use App\Form\MenuType;
use App\Form\MenuSeccionType;
use App\Repository\MenuRepository;
use App\Repository\MenuSeccionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/menu')]
class MenuController extends AbstractController
{
    public function __construct(
        private MenuSeccionRepository $menuSeccionRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/', name: 'app_admin_menu_index', methods: ['GET'])]
    public function index(MenuRepository $menuRepository): Response
    {
        return $this->render('admin/menu/index.html.twig', [
            'menus' => $menuRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_menu_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $menu = new Menu();
        $form = $this->createForm(MenuType::class, $menu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($menu);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_admin_menu_index');
        }

        return $this->render('admin/menu/new.html.twig', [
            'menu' => $menu,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_menu_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Menu $menu): Response
    {
        $form = $this->createForm(MenuType::class, $menu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            return $this->redirectToRoute('app_admin_menu_index');
        }

        return $this->render('admin/menu/edit.html.twig', [
            'menu' => $menu,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_admin_menu_delete', methods: ['POST'])]
    public function delete(Request $request, Menu $menu): Response
    {
        if ($this->isCsrfTokenValid('delete'.$menu->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($menu);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_menu_index');
    }

    #[Route('/{id}/secciones', name: 'app_admin_menu_secciones', methods: ['GET'])]
    public function secciones(Menu $menu): Response
    {
        $menuSecciones = $this->menuSeccionRepository->findBy(['menu' => $menu], ['orden' => 'ASC']);
        
        return $this->render('admin/menu/secciones.html.twig', [
            'menu' => $menu,
            'menuSecciones' => $menuSecciones
        ]);
    }

    #[Route('/{id}/agregar-seccion', name: 'app_admin_menu_agregar_seccion', methods: ['GET', 'POST'])]
    public function agregarSeccion(Request $request, Menu $menu): Response
    {
        $menuSeccion = new MenuSeccion();
        $menuSeccion->setMenu($menu);
        
        $form = $this->createForm(MenuSeccionType::class, $menuSeccion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($menuSeccion);
            $this->entityManager->flush();

            $this->addFlash('success', 'Sección agregada correctamente');
            return $this->redirectToRoute('app_admin_menu_secciones', ['id' => $menu->getId()]);
        }

        return $this->render('admin/menu/agregar_seccion.html.twig', [
            'menu' => $menu,
            'form' => $form->createView()
        ]);
    }

    #[Route('/menu-seccion/{id}/editar', name: 'app_admin_menu_seccion_editar', methods: ['GET', 'POST'])]
    public function editarSeccion(Request $request, MenuSeccion $menuSeccion): Response
    {
        $form = $this->createForm(MenuSeccionType::class, $menuSeccion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Sección actualizada correctamente');
            return $this->redirectToRoute('app_admin_menu_secciones', ['id' => $menuSeccion->getMenu()->getId()]);
        }

        return $this->render('admin/menu/editar_seccion.html.twig', [
            'menuSeccion' => $menuSeccion,
            'form' => $form->createView()
        ]);
    }

    #[Route('/menu-seccion/{id}/eliminar', name: 'app_admin_menu_seccion_eliminar', methods: ['POST'])]
    public function eliminarSeccion(Request $request, MenuSeccion $menuSeccion): Response
    {
        if ($this->isCsrfTokenValid('delete'.$menuSeccion->getId(), $request->request->get('_token'))) {
            $menuId = $menuSeccion->getMenu()->getId();
            $this->entityManager->remove($menuSeccion);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Sección eliminada correctamente');
        }

        return $this->redirectToRoute('app_admin_menu_secciones', ['id' => $menuId]);
    }
}