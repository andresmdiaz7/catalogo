<?php

namespace App\Repository;

use App\Entity\Cliente;
use App\Entity\Usuario;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ClienteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cliente::class);
    }

    /**
     * Busca clientes por filtros, en el panel de administración, padron de clientes
     * @param array $filters Filtros para la búsqueda
     * @return array Lista de clientes encontrados
     */
    public function findByFilters(array $filters = [])
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.localidad', 'l')
            ->leftJoin('c.vendedor', 'v')
            ->leftJoin('c.usuario', 'u');
        
        if (!empty($filters['codigoOrRazonSocial'])) {
            $qb->andWhere('c.codigo LIKE :search OR c.razonSocial LIKE :search')
               ->setParameter('search', '%' . $filters['codigoOrRazonSocial'] . '%');
        }
        
        if (!empty($filters['localidad'])) {
            $qb->andWhere('c.localidad = :localidad')
               ->setParameter('localidad', $filters['localidad']);
        }
        
        if (!empty($filters['vendedor'])) {
            $qb->andWhere('c.vendedor = :vendedor')
               ->setParameter('vendedor', $filters['vendedor']);
        }
        
        if (isset($filters['habilitado'])) {
            $qb->andWhere('c.habilitado = :habilitado')
               ->setParameter('habilitado', $filters['habilitado']);
        }

        if (!empty($filters['email'])) {
            $qb->andWhere('u.email LIKE :email')
               ->setParameter('email', '%' . $filters['email'] . '%');
        }
        
        return $qb->orderBy('c.razonSocial', 'ASC')
                  ->getQuery()
                  ->getResult();
    }
} 