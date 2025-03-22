<?php

namespace App\Controller\Admin;

use App\Entity\Marca;
use App\Form\MarcaType;
use App\Repository\MarcaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/admin/marca')]
class MarcaController extends AbstractController
{
    #[Route('/', name: 'app_admin_marca_index', methods: ['GET'])]
    public function index(Request $request, MarcaRepository $marcaRepository, PaginatorInterface $paginator): Response
    {
        $queryBuilder = $marcaRepository->createQueryBuilder('m');

        if ($searchTerm = $request->query->get('search')) {
            $queryBuilder
                ->where('m.codigo LIKE :search')
                ->orWhere('m.nombre LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        }

        if ($habilitado = $request->query->get('habilitado')) {
            $queryBuilder
                ->andWhere('m.habilitado = :habilitado')
                ->setParameter('habilitado', $habilitado === 'true');
        }

        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('admin/marca/index.html.twig', [
            'marcas' => $pagination,
            'searchTerm' => $searchTerm,
            'habilitado' => $habilitado
        ]);
    }

    #[Route('/new', name: 'app_admin_marca_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $marca = new Marca();
        $form = $this->createForm(MarcaType::class, $marca);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($marca);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_marca_index');
        }

        return $this->render('admin/marca/new.html.twig', [
            'marca' => $marca,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{codigo}/edit', name: 'app_admin_marca_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Marca $marca, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MarcaType::class, $marca);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_marca_index');
        }

        return $this->render('admin/marca/edit.html.twig', [
            'marca' => $marca,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{codigo}', name: 'app_admin_marca_show', methods: ['GET'])]
    public function show(Marca $marca): Response
    {
        return $this->render('admin/marca/show.html.twig', [
            'marca' => $marca,
        ]);
    }

    #[Route('/{codigo}', name: 'app_admin_marca_delete', methods: ['POST'])]
    public function delete(Request $request, Marca $marca, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$marca->getCodigo(), $request->request->get('_token'))) {
            $entityManager->remove($marca);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_marca_index');
    }
}
