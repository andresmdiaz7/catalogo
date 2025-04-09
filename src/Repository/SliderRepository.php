<?php

namespace App\Repository;

use App\Entity\Slider;
use App\Entity\Cliente;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SliderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Slider::class);
    }

    /**
     * Busca los sliders activos para una ubicación específica y si existe un cliente, filtra por categoría
     * Es que se utiliza en una extensión de twig para obtener los sliders de una ubicación específica
     * @param string $ubicacionCodigo El código de la ubicación
     * @param Cliente|null $cliente El cliente a filtrar, o null para no filtrar por cliente
     * @return Slider[] Los sliders activos para la ubicación
     */
    public function findSlidersPorUbicacion(string $ubicacionCodigo, $cliente = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->join('s.ubicacion', 'u')
            ->where('u.codigo = :codigo')
            ->andWhere('s.activo = :activo')
            ->andWhere('s.fechaInicio <= :ahora')
            ->andWhere('s.fechaFin >= :ahora')
            ->setParameter('codigo', $ubicacionCodigo)
            ->setParameter('activo', true)
            ->setParameter('ahora', new \DateTime())
            ->orderBy('s.orden', 'ASC');

        // Si hay un cliente que es de tipo Cliente, filtrar por categoría
        if ($cliente instanceof Cliente && $cliente->getCategoria()) {
            $qb->andWhere('s.categoria IS NULL OR s.categoria = :categoria')
               ->setParameter('categoria', $cliente->getCategoria());
        } else {
            // Si no hay cliente de tipo Cliente, solo mostrar sliders sin categoría
            $qb->andWhere('s.categoria IS NULL');
        }

        return $qb->getQuery()->getResult();
    }
}
