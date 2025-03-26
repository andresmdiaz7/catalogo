<?php

namespace App\Repository;

use App\Entity\Archivo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Archivo>
 *
 * @method Archivo|null find($id, $lockMode = null, $lockVersion = null)
 * @method Archivo|null findOneBy(array $criteria, array $orderBy = null)
 * @method Archivo[]    findAll()
 * @method Archivo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArchivoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Archivo::class);
    }

    /**
     * Guarda un nuevo archivo en la base de datos
     */
    public function save(Archivo $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Elimina un archivo de la base de datos
     */
    public function remove(Archivo $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Encuentra un archivo por su hash MD5
     */
    public function findOneByHash(string $hash): ?Archivo
    {
        return $this->findOneBy(['hash' => $hash]);
    }

    /**
     * Encuentra archivos no asociados a ningún artículo
     */
    public function findHuerfanos(): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.articuloArchivos', 'aa')
            ->groupBy('a.id')
            ->having('COUNT(aa.id) = 0')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca archivos por nombre o tipo
     */
    public function buscarPorNombre(string $termino): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.file_name LIKE :termino')
            ->setParameter('termino', '%' . $termino . '%')
            ->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra los archivos más utilizados (con más asociaciones a artículos)
     */
    public function findMasUtilizados(int $limit = 10): array
    {
        return $this->createQueryBuilder('a')
            ->select('a, COUNT(aa.id) as total')
            ->leftJoin('a.articuloArchivos', 'aa')
            ->groupBy('a.id')
            ->orderBy('total', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra archivos por tipo MIME (ej: 'image/%' para todas las imágenes)
     */
    public function findByTipoMime(string $tipoMime): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.tipoArchivo LIKE :tipo')
            ->setParameter('tipo', $tipoMime)
            ->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra archivos potencialmente duplicados (mismos nombres pero diferentes hash)
     */
    public function findPosiblesDuplicados(): array
    {
        return $this->createQueryBuilder('a1')
            ->select('a1.nombreArchivo, COUNT(a1.id) as total')
            ->groupBy('a1.nombreArchivo')
            ->having('total > 1')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Estadísticas de archivos por tipo
     */
    public function estadisticasPorTipo(): array
    {
        $tipos = $this->createQueryBuilder('a')
            ->select('SUBSTRING(a.tipoArchivo, 1, LOCATE(\'/\', a.tipoArchivo) - 1) as tipo, COUNT(a.id) as total')
            ->groupBy('tipo')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();
            
        return $tipos;
    }

    /**
     * Buscar archivos por filtros y paginados
     */
    public function buscarPorFiltros(string $query = '', string $tipo = '', string $fechaDesde = '', string $fechaHasta = '', int $pagina = 1, int $porPagina = 12): array
    {
        $qb = $this->createQueryBuilder('a');
        
        // Filtro por nombre
        if (!empty($query)) {
            $qb->andWhere('a.file_name LIKE :query OR a.file_path LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }
        
        // Filtro por tipo
        if (!empty($tipo)) {
            if ($tipo === 'imagen') {
                $qb->andWhere('a.tipoMime LIKE :tipo')
                   ->setParameter('tipo', 'image/%');
            } elseif ($tipo === 'documento') {
                $qb->andWhere('a.tipoMime LIKE :doc1 OR a.tipoMime LIKE :doc2 OR a.tipoMime LIKE :doc3')
                   ->setParameter('doc1', 'application/pdf')
                   ->setParameter('doc2', 'application/msword')
                   ->setParameter('doc3', 'application/vnd.openxmlformats-officedocument.wordprocessingml%');
            } elseif ($tipo === 'video') {
                $qb->andWhere('a.tipoMime LIKE :video')
                   ->setParameter('video', 'video/%');
            }
        }
        
        // Filtro por fecha
        if (!empty($fechaDesde)) {
            $qb->andWhere('a.createdAt >= :fechaDesde')
               ->setParameter('fechaDesde', new \DateTime($fechaDesde));
        }
        
        if (!empty($fechaHasta)) {
            $qb->andWhere('a.createdAt <= :fechaHasta')
               ->setParameter('fechaHasta', new \DateTime($fechaHasta . ' 23:59:59'));
        }
        
        // Ordenar por fecha de creación (más reciente primero)
        $qb->orderBy('a.id', 'DESC');
        
        // Contar total de resultados
        $totalQb = clone $qb;
        $totalItems = count($totalQb->getQuery()->getResult());
        
        // Paginación
        $offset = ($pagina - 1) * $porPagina;
        $qb->setFirstResult($offset)
           ->setMaxResults($porPagina);
        
        return [
            'archivos' => $qb->getQuery()->getResult(),
            'total' => $totalItems
        ];
    }
}