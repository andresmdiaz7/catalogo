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

        $qb->orderBy('a.codigo', 'ASC');

        return $qb;
    }
} 