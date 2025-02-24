<?php

namespace App\Repository;

use App\Entity\Pedido;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PedidoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pedido::class);
    }

    /**
     * @return Pedido[] Returns an array of Pedido objects
     */
    public function findByCliente($cliente): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.cliente = :cliente')
            ->setParameter('cliente', $cliente)
            ->orderBy('p.fecha', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByFilters(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.cliente', 'c')
            ->addSelect('c');

        if (!empty($filters['cliente'])) {
            $qb->andWhere('c.id = :cliente')
                ->setParameter('cliente', $filters['cliente']);
        }

        if (!empty($filters['estado'])) {
            $qb->andWhere('p.estado = :estado')
                ->setParameter('estado', $filters['estado']);
        }

        if (!empty($filters['desde'])) {
            $qb->andWhere('p.fechaPedido >= :desde')
                ->setParameter('desde', $filters['desde']);
        }

        if (!empty($filters['hasta'])) {
            $qb->andWhere('p.fechaPedido <= :hasta')
                ->setParameter('hasta', $filters['hasta']);
        }

        $qb->orderBy('p.fechaPedido', 'DESC');

        return $qb->getQuery()->getResult();
    }
} 