<?php

namespace App\Repository;

use App\Entity\Articulo;
use App\Entity\Cliente;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class ArticuloRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Articulo::class);
    }

    public function findByFilters(array $filters = [], bool $onlyEnabled = true): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.subrubro', 's')
            ->leftJoin('s.rubro', 'r');

        if ($onlyEnabled) {
            $qb->andWhere('a.habilitadoWeb = true');
            $qb->andWhere('a.habilitadoGestion = true');
            $qb->andWhere('a.precioLista > 0');
        }



        if (!empty($filters['search'])) {
            $qb->andWhere('a.codigo LIKE :search OR a.detalle LIKE :search OR a.marca LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['rubro'])) {
            $qb->andWhere('r.id = :rubro')
                ->setParameter('rubro', $filters['rubro']);
        }

        if (!empty($filters['subrubro'])) {
            $qb->andWhere('s.id = :subrubro')
                ->setParameter('subrubro', $filters['subrubro']);
        }

        if (!empty($filters['marca'])) {
            $qb->andWhere('a.marca = :marca')
                ->setParameter('marca', $filters['marca']);
        }

        if (isset($filters['destacado'])) {
            $qb->andWhere('a.destacado = :destacado')
                ->setParameter('destacado', $filters['destacado']);
        }

        if (isset($filters['novedad'])) {
            $qb->andWhere('a.novedad = :novedad')
                ->setParameter('novedad', $filters['novedad']);
        }

        // Ordenamiento
        if (!empty($filters['orderBy'])) {
            $direction = !empty($filters['orderDir']) ? $filters['orderDir'] : 'ASC';
            $qb->orderBy('a.' . $filters['orderBy'], $direction);
        } else {
            $qb->orderBy('a.detalle', 'ASC');
        }

        return $qb->getQuery()->getResult();
    }

    public function findMarcas(): array
    {
        return $this->createQueryBuilder('a')
            ->select('DISTINCT a.marca')
            ->where('a.marca IS NOT NULL')
            ->andWhere('a.habilitadoWeb = true')
            ->orderBy('a.marca', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function createQueryBuilderWithFilters(array $filters = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.subrubro', 's')
            ->leftJoin('s.rubro', 'r')
            ->leftJoin('a.marca', 'm')
            ->addSelect('s', 'r', 'm');

        if (!empty($filters['buscar'])) {
            $palabras = explode(' ', trim($filters['buscar']));
            foreach ($palabras as $index => $palabra) {
                $paramName = 'buscar' . $index;
                $qb->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->like('a.codigo', ':' . $paramName),
                        $qb->expr()->like('a.detalle', ':' . $paramName),
                        $qb->expr()->like('m.nombre', ':' . $paramName)
                    )
                )
                ->setParameter($paramName, '%' . $palabra . '%');
            }
        }

        if (!empty($filters['rubro'])) {
            $qb->andWhere('r.codigo = :rubro')
                ->setParameter('rubro', $filters['rubro']);
        }

        if (!empty($filters['subrubro'])) {
            $qb->andWhere('s.codigo = :subrubro')
                ->setParameter('subrubro', $filters['subrubro']);
        }

        if (!empty($filters['marca'])) {
            $qb->andWhere('m.codigo = :marca')
                ->setParameter('marca', $filters['marca']);
        }

        if (isset($filters['habilitadoWeb']) && $filters['habilitadoWeb'] !== '') {
            $qb->andWhere('a.habilitadoWeb = :habilitadoWeb')
                ->setParameter('habilitadoWeb', $filters['habilitadoWeb']);
        }

        if (isset($filters['habilitadoGestion']) && $filters['habilitadoGestion'] !== '') {
            $qb->andWhere('a.habilitadoGestion = :habilitadoGestion')
                ->setParameter('habilitadoGestion', $filters['habilitadoGestion']);
        }

        if (isset($filters['destacado']) && $filters['destacado'] !== '') {
            $qb->andWhere('a.destacado = :destacado')
                ->setParameter('destacado', $filters['destacado']);
        }
        
        if (isset($filters['novedad']) && $filters['novedad'] !== '') {
            $qb->andWhere('a.novedad = :novedad')
                ->setParameter('novedad', $filters['novedad']);
        }

        // Filtro de imagen
        if (isset($filters['tieneImagen']) && $filters['tieneImagen'] !== '') {
            if ($filters['tieneImagen'] === '0') {
                // Sin imagen
                $qb->andWhere(
                    $qb->expr()->not(
                        $qb->expr()->exists(
                            $this->createQueryBuilder('a2')
                                ->select('1')
                                ->leftJoin('a2.archivos', 'aa2')
                                ->leftJoin('aa2.archivo', 'ar2')
                                ->where('a2.codigo = a.codigo')
                                ->andWhere('ar2.tipoMime LIKE :imageMime')
                                ->getDQL()
                        )
                    )
                )->setParameter('imageMime', 'image/%');
            } elseif ($filters['tieneImagen'] === '1') {
                // Con imagen
                $qb->andWhere(
                    $qb->expr()->exists(
                        $this->createQueryBuilder('a3')
                            ->select('1')
                            ->leftJoin('a3.archivos', 'aa3')
                            ->leftJoin('aa3.archivo', 'ar3')
                            ->where('a3.codigo = a.codigo')
                            ->andWhere('ar3.tipoMime LIKE :imageMime2')
                            ->getDQL()
                    )
                )->setParameter('imageMime2', 'image/%');
            }
        }

        // Filtro por fecha de creación
        if (!empty($filters['fechaDesde'])) {
            $qb->andWhere('a.fechaCreacion >= :fechaDesde')
                ->setParameter('fechaDesde', new \DateTime($filters['fechaDesde']));
        }

        if (!empty($filters['fechaHasta'])) {
            $fechaHasta = new \DateTime($filters['fechaHasta']);
            $fechaHasta->setTime(23, 59, 59); // Final del día
            $qb->andWhere('a.fechaCreacion <= :fechaHasta')
                ->setParameter('fechaHasta', $fechaHasta);
        }

        $qb->orderBy('a.codigo', 'ASC');

        return $qb;
    }

} 