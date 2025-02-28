<?php

namespace App\Controller;

use App\Repository\ArticuloRepository;
use App\Repository\RubroRepository;
use App\Repository\SubrubroRepository;
use App\Repository\SeccionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use App\Service\CartService;
use App\Entity\Articulo;
use App\Entity\Cliente;
use App\Service\ArticuloPrecioService;
use Symfony\Bundle\SecurityBundle\Security;
use App\Service\MenuService;


class CatalogoController extends AbstractController
{
    private $articuloPrecioService;

    public function __construct(ArticuloPrecioService $articuloPrecioService, private Security $security, private MenuService $menuService)
    {
        $this->articuloPrecioService = $articuloPrecioService;
    }

    #[Route('/catalogo', name: 'app_catalogo_index')]
    public function index(Request $request, ArticuloRepository $articuloRepository, PaginatorInterface $paginator): Response
    {
        

        $busqueda = $request->query->get('buscar');
        
        $qb = $articuloRepository->createQueryBuilder('a')
            ->where('a.habilitadoWeb = :habilitadoWeb')
            ->andWhere('a.habilitadoGestion = :habilitadoGestion')
            ->andWhere('a.precioLista > :precio')
            ->setParameter('habilitadoWeb', true)
            ->setParameter('habilitadoGestion', true)
            ->setParameter('precio', 0)
            ->orderBy('a.destacado', 'DESC')
            ->addOrderBy('a.codigo', 'DESC');

        if ($busqueda) {
            // Separar los términos de búsqueda y eliminar espacios en blanco
            $terminos = array_filter(explode(' ', trim($busqueda)));
            
            if (!empty($terminos)) {
                $whereConditions = [];
                $parameters = [];
                
                foreach ($terminos as $key => $termino) {
                    $paramName = 'busqueda_' . $key;
                    $whereConditions[] = "(a.codigo LIKE :$paramName OR 
                                         a.detalle LIKE :$paramName OR 
                                         a.modelo LIKE :$paramName OR 
                                         a.marca LIKE :$paramName)";
                    $parameters[$paramName] = '%' . $termino . '%';
                }
                
                // Combinar todas las condiciones con AND
                $qb->andWhere(implode(' AND ', $whereConditions));
                
                // Establecer todos los parámetros
                foreach ($parameters as $key => $value) {
                    $qb->setParameter($key, $value);
                }
            }
        }

        $query = $qb->getQuery();

        $articulos = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            12 // número de elementos por página
        );

        return $this->render('catalogo/index.html.twig', [
            'articulos' => $articulos,
            'busqueda' => $busqueda
        ]);
    }

    #[Route('/catalogo/{codigo}', name: 'app_catalogo_show')]
    public function show(string $codigo, ArticuloRepository $articuloRepository): Response
    {
        $articulo = $articuloRepository->findOneBy(['codigo' => $codigo]);
        
        if (!$articulo) {
            throw $this->createNotFoundException('El artículo no existe.');
        }

        return $this->render('catalogo/show.html.twig', [
            'articulo' => $articulo,
        ]);
    }

    #[Route('/catalogo/seccion/{id}/{rubro?}/{subrubro?}', name: 'app_catalogo_seccion')]
    public function porSeccion(
        Request $request,
        SeccionRepository $seccionRepository,
        RubroRepository $rubroRepository,
        SubrubroRepository $subrubroRepository,
        ArticuloRepository $articuloRepository,
        PaginatorInterface $paginator,
        string $id,
        ?string $rubro = null,
        ?string $subrubro = null
    ): Response {

        $seccion = $seccionRepository->createQueryBuilder('s')
            ->leftJoin('s.rubros', 'r')
            ->addSelect('r')
            ->where('s.id = :id')
            ->setParameter('id', $id)
            ->orderBy('r.nombre', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();

        if (!$seccion) {
            throw $this->createNotFoundException('La sección no existe'); 
        }

        // Iniciar la consulta de artículos
        $queryBuilder = $articuloRepository->createQueryBuilder('a')
            ->leftJoin('a.subrubro', 's')
            ->leftJoin('s.rubro', 'r')
            ->where('r.seccion = :seccion')
            ->andWhere('a.habilitadoWeb = :habilitado')
            ->andWhere('a.precioLista > :precio')
            ->setParameter('seccion', $seccion)
            ->setParameter('habilitado', true)
            ->setParameter('precio', 0);

        // Obtener rubro y subrubro activos si existen
        $rubroActual = null;
        $subrubroActual = null;

        if ($rubro) {
            $rubroActual = $rubroRepository->findOneBy(['codigo' => $rubro]);
            if ($rubroActual) {
                $queryBuilder->andWhere('r.codigo = :rubro')->setParameter('rubro', $rubro);
            }
        }

        if ($subrubro) {
            $subrubroActual = $subrubroRepository->findOneBy(['codigo' => $subrubro]);
            if ($subrubroActual) {
                $queryBuilder->andWhere('s.codigo = :subrubro')->setParameter('subrubro', $subrubro);
            }
        }

        $queryBuilder
            ->orderBy('a.destacado', 'DESC')
            ->addOrderBy('a.codigo', 'DESC');
            //->addOrderBy('a.detalle', 'ASC');

        $articulos = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            12
        );
        
        return $this->render('catalogo/seccion.html.twig', [
            'seccion' => $seccion,
            'articulos' => $articulos,
            'rubro_actual' => $rubroActual,
            'subrubro_actual' => $subrubroActual
        ]);
    }


    #[Route('/catalogo/subrubro/{codigo}', name: 'app_catalogo_subrubro')]
    public function porSubrubro(
        string $codigo, 
        SubrubroRepository $subrubroRepository, 
        ArticuloRepository $articuloRepository,
        RubroRepository $rubroRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $subrubro = $subrubroRepository->findOneBy(['codigo' => $codigo]);
        
        if (!$subrubro) {
            throw $this->createNotFoundException('El subrubro no existe');
        }

        $query = $articuloRepository->createQueryBuilder('a')
            ->where('a.subrubro = :subrubro')
            ->andWhere('a.habilitadoWeb = :habilitado')
            ->setParameter('subrubro', $subrubro)
            ->setParameter('habilitado', true)
            ->orderBy('a.detalle', 'ASC')
            ->getQuery();

        $articulos = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            12
        );

        // Consulta de rubros con sus subrubros
        $rubros = $rubroRepository->createQueryBuilder('r')
            ->leftJoin('r.subrubros', 's')
            ->addSelect('s')
            ->orderBy('r.nombre', 'ASC')
            ->addOrderBy('s.nombre', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('catalogo/subrubro.html.twig', [
            'subrubro' => $subrubro,
            'articulos' => $articulos,
            'rubro' => $subrubro->getRubro(),
            'rubros' => $rubros
        ]);
    }
}