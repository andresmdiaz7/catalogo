<?php

namespace App\Repository;

use App\Entity\CarritoItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CarritoItem>
 *
 * @method CarritoItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method CarritoItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method CarritoItem[]    findAll()
 * @method CarritoItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CarritoItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CarritoItem::class);
    }
}
