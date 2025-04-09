<?php
namespace App\Repository;

use App\Entity\SliderArchivo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SliderArchivoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SliderArchivo::class);
    }

    public function findArchivosPorSlider(int $sliderId): array
    {
        return $this->createQueryBuilder('sa')
            ->where('sa.slider = :sliderId')
            ->setParameter('sliderId', $sliderId)
            ->orderBy('sa.orden', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
