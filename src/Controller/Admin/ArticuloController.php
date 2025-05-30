<?php

namespace App\Controller\Admin;

use App\Entity\Articulo;
use App\Form\ArticuloType;
use App\Repository\ArticuloRepository;
use App\Repository\RubroRepository;
use App\Repository\MarcaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;

/**
 * Controlador para administrar los artículos.
 * permite crear, editar y eliminar artículos.
 * También permite alternar el estado de habilitación de los artículos.
 * @Route("/admin/articulos")
 */
#[Route('/admin/articulos')]
#[IsGranted('ROLE_ADMIN')]
class ArticuloController extends AbstractController
{
    /**
     * Muestra el listado de articulos para administrarlos, buscador, filtros y paginación.
     * @param Request $request
     * @param ArticuloRepository $articuloRepository
     * @param RubroRepository $rubroRepository
     * @param PaginatorInterface $paginator
     * @return Response
     */
    #[Route('/', name: 'app_admin_articulo_index', methods: ['GET'])]
    public function index(
        Request $request, 
        ArticuloRepository $articuloRepository,
        RubroRepository $rubroRepository,
        MarcaRepository $marcaRepository,
        PaginatorInterface $paginator
    ): Response {
        $filters = $request->query->all();
        // Query builder desde repositorio

        // Establecer el límite de resultados por página desde los filtros
        $limit = isset($filters['limit']) ? (int)$filters['limit'] : 10;

        $queryBuilder = $articuloRepository->createQueryBuilderWithFilters($filters);
        
        $pagination = $paginator->paginate(
            $queryBuilder, // Query builder desde repositorio
            $request->query->getInt('page', 1), // Página actual
            $limit // Límite por página
        );

        $subrubros = !empty($filters['rubro']) 
            ? $rubroRepository->find($filters['rubro'])?->getSubrubros() ?? []
            : $rubroRepository->findAll();

        $marcas = $marcaRepository->findBy([], ['nombre' => 'ASC']);

        return $this->render('admin/articulo/index.html.twig', [
            'articulos' => $pagination,
            'filtros' => $filters,
            'rubros' => $rubroRepository->findAll(),
            'subrubros' => $subrubros,
            'marcas' => $marcas
        ]);
    }
    

    /**
     * Edita el artículo seleccionado.
     * @param Request $request
     * @param Articulo $articulo
     * */
    #[Route('/{codigo}/editar', name: 'app_admin_articulo_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        #[MapEntity(id: 'codigo')] Articulo $articulo, 
        EntityManagerInterface $entityManager,
    ): Response {
        $form = $this->createForm(ArticuloType::class, $articulo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($articulo);
                $entityManager->flush();
                $this->addFlash('success', 'Artículo actualizado correctamente');
            } catch (FileException $e) {
                $this->addFlash('error', 'Error al actualizar el archivo: ' . $e->getMessage());
            }
            return $this->redirectToRoute('app_admin_articulo_edit', ['codigo' => $articulo->getCodigo()]);
        }

        return $this->render('admin/articulo/edit.html.twig', [
            'articulo' => $articulo,
            'form' => $form,
        ]);
    }

    /**
     * Alterna el estado de habilitación del artículo.
     * Si el artículo está habilitado, se deshabilita y viceversa.
     */
    #[Route('/{codigo}/alternar-habilitado', name: 'app_admin_articulo_alternar_habilitado', methods: ['POST','GET'])]
    public function toggleHabilitado(
        Request $request, 
        #[MapEntity(id: 'codigo')] Articulo $articulo, 
        EntityManagerInterface $entityManager
    ): Response {
        $articulo->setHabilitadoWeb(!$articulo->isHabilitadoWeb());
        $entityManager->flush();

        $this->addFlash(
            'success',
            'El artículo <strong>' . $articulo->getCodigo() .' - '. $articulo->getDetalle() . '</strong> 
            ha sido ' . ($articulo->isHabilitadoWeb() ? '<span class="badge text-bg-success"><i class="bi bi-check-circle pe-1"></i> Habilitado</span>' : '<span class="badge text-bg-danger"><i class="bi bi-x-circle pe-1"></i> Deshabilitado</span>')
        );

        // Obtener los parámetros de la URL actual
        $queryParams = $request->headers->get('referer') ? parse_url($request->headers->get('referer'), PHP_URL_QUERY) : '';
        parse_str($queryParams, $queryParams);
        
        $url = $this->generateUrl('app_admin_articulo_index', $queryParams);
        
        return $this->redirect($url);
    }

    
} 