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
    public function buscarPorNombreOTipo(string $termino): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.nombreArchivo LIKE :termino')
            ->orWhere('a.tipoArchivo LIKE :termino')
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
}