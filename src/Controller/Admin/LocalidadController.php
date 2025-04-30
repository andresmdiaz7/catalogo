<?php

namespace App\Controller\Admin;

use App\Entity\Localidad;
use App\Form\LocalidadType;
use App\Repository\LocalidadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/admin/localidades')]
class LocalidadController extends AbstractController
{
    public function __construct(
        private PaginatorInterface $paginator
    ) {
    }

    #[Route('/', name: 'app_admin_localidad_index', methods: ['GET'])]
    public function index(Request $request, LocalidadRepository $localidadRepository): Response
    {
        $queryBuilder = $localidadRepository->createQueryBuilder('l')
            ->orderBy('l.nombre', 'ASC');

        // Agregar filtro de búsqueda
        if ($search = $request->query->get('search')) {
            $queryBuilder
                ->andWhere('l.nombre LIKE :search OR l.provincia LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        // Filtro por habilitado
        if ($habilitado = $request->query->get('habilitado')) {
            $queryBuilder
                ->andWhere('l.habilitado = :habilitado')
                ->setParameter('habilitado', $habilitado === 'true');
        }

        $pagination = $this->paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('admin/localidad/index.html.twig', [
            'localidades' => $pagination,
            'searchTerm' => $search,
            'habilitado' => $habilitado
        ]);
    }

    #[Route('/nuevo', name: 'app_admin_localidad_nuevo', methods: ['GET', 'POST'])]
    public function nuevo(Request $request, EntityManagerInterface $entityManager): Response
    {
        $localidad = new Localidad();
        $form = $this->createForm(LocalidadType::class, $localidad);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($localidad);
            $entityManager->flush();

            $this->addFlash('success', sprintf(
                'Localidad "%s" creada correctamente',
                $localidad->getNombre()
            ));
            return $this->redirectToRoute('app_admin_localidad_index');
        }

        return $this->render('admin/localidad/nuevo.html.twig', [
            'localidad' => $localidad,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/editar', name: 'app_admin_localidad_editar', methods: ['GET', 'POST'])]
    public function editar(Request $request, Localidad $localidad, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LocalidadType::class, $localidad);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', sprintf(
                'Localidad "%s" actualizada correctamente',
                $localidad->getNombre()
            ));
            return $this->redirectToRoute('app_admin_localidad_index');
        }

        return $this->render('admin/localidad/editar.html.twig', [
            'localidad' => $localidad,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/eliminar', name: 'app_admin_localidad_eliminar', methods: ['POST'])]
    public function eliminar(Request $request, Localidad $localidad, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$localidad->getId(), $request->request->get('_token'))) {
            $nombre = $localidad->getNombre();
            $entityManager->remove($localidad);
            $entityManager->flush();
            
            $this->addFlash('success', sprintf(
                'Localidad "%s" eliminada correctamente',
                $nombre
            ));
        }

        return $this->redirectToRoute('app_admin_localidad_index');
    }
} 