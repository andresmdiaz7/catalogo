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