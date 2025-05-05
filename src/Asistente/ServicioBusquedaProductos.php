<?php

namespace App\Asistente;

use App\Repository\ArticuloRepository;
use App\Repository\RubroRepository;
use App\Repository\SubrubroRepository;

/**
 * Servicio para buscar productos en la base de datos según la consulta del usuario.
 */
class ServicioBusquedaProductos
{
    public function __construct(
        private ArticuloRepository $articuloRepository,
        private RubroRepository $rubroRepository,
        private SubrubroRepository $subrubroRepository
    ) {}

    /**
     * Busca productos por palabras clave en detalle y marca.
     */
    public function buscarPorDetalleYMarca(string $consulta): array
    {
        // Separar la consulta en palabras clave
        $palabras = array_filter(explode(' ', trim($consulta)));
        if (empty($palabras)) {
            return [];
        }
        
        // Buscar coincidencias en rubros y subrubros
        $rubroEncontrado = $this->buscarRubroPorPalabras($palabras);
        $subrubroEncontrado = $this->buscarSubrubroPorPalabras($palabras);

        $qb = $this->articuloRepository->createQueryBuilder('a')
            ->leftJoin('a.marca', 'm')
            ->leftJoin('a.subrubro', 'sr')
            ->leftJoin('sr.rubro', 'r')
            ->andWhere('a.habilitadoWeb = :habilitadoWeb')
            ->andWhere('a.habilitadoGestion = :habilitadoGestion')
            ->setParameter('habilitadoWeb', true)
            ->setParameter('habilitadoGestion', true)
            ->setMaxResults(10);

        // Si se encontró subrubro, filtrar por subrubro
        if ($subrubroEncontrado) {
            $qb->andWhere('sr.codigo = :subrubroCodigo')
               ->setParameter('subrubroCodigo', $subrubroEncontrado->getCodigo());
        }
        // Si no hay subrubro pero sí rubro, filtrar por rubro
        elseif ($rubroEncontrado) {
            $qb->andWhere('r.codigo = :rubroCodigo')
               ->setParameter('rubroCodigo', $rubroEncontrado->getCodigo());
        }

        // Primera pasada: búsqueda estricta (AND)
        $whereConditions = [];
        $parameters = [];
        foreach ($palabras as $i => $palabra) {
            $param = 'busqueda_' . $i;
            $whereConditions[] = "LOWER(a.detalle) LIKE :$param";
            $parameters[$param] = '%' . mb_strtolower($palabra) . '%';
        }
        if (!empty($whereConditions)) {
            $qb->andWhere(implode(' AND ', $whereConditions));
            foreach ($parameters as $key => $value) {
                $qb->setParameter($key, $value);
            }
        }

        $resultados = $qb->getQuery()->getResult();

        // DEBUG: Ver productos encontrados en la primera pasada
        
        dump('Productos encontrados (estricto):', count($resultados), $resultados);
        

        // Si no hay resultados, hacer una búsqueda menos estricta (OR)
        if (count($resultados) === 0 && count($palabras) > 1) {
            $qb = $this->articuloRepository->createQueryBuilder('a')
                ->leftJoin('a.marca', 'm')
                ->leftJoin('a.subrubro', 'sr')
                ->leftJoin('sr.rubro', 'r')
                ->andWhere('a.habilitadoWeb = :habilitadoWeb')
                ->andWhere('a.habilitadoGestion = :habilitadoGestion')
                ->setParameter('habilitadoWeb', true)
                ->setParameter('habilitadoGestion', true)
                ->setMaxResults(10);

            if ($subrubroEncontrado) {
                $qb->andWhere('sr.codigo = :subrubroCodigo')
                   ->setParameter('subrubroCodigo', $subrubroEncontrado->getCodigo());
            } elseif ($rubroEncontrado) {
                $qb->andWhere('r.codigo = :rubroCodigo')
                   ->setParameter('rubroCodigo', $rubroEncontrado->getCodigo());
            }

            $whereConditions = [];
            $parameters = [];
            foreach ($palabras as $i => $palabra) {
                $param = 'busqueda_' . $i;
                $whereConditions[] = "LOWER(a.detalle) LIKE :$param";
                $parameters[$param] = '%' . mb_strtolower($palabra) . '%';
            }
            if (!empty($whereConditions)) {
                $qb->andWhere(implode(' OR ', $whereConditions));
                foreach ($parameters as $key => $value) {
                    $qb->setParameter($key, $value);
                }
            }

            if (!in_array('portalámpara', array_map('mb_strtolower', $palabras))) {
                $qb->andWhere('LOWER(a.detalle) NOT LIKE :excluir_portalampara');
                $qb->setParameter('excluir_portalampara', '%portalámpara%');
            }

            $resultados = $qb->getQuery()->getResult();

            // DEBUG: Ver productos encontrados en la segunda pasada
            if (function_exists('dump')) {
                dump('Productos encontrados (flexible):', count($resultados), $resultados);
            }
        }

        return $resultados;
    }

    /**
     * Busca un rubro que coincida con alguna de las palabras clave.
     */
    private function buscarRubroPorPalabras(array $palabras): ?\App\Entity\Rubro
    {
        // Suponiendo que tienes un RubroRepository inyectado
        foreach ($palabras as $palabra) {
            $rubro = $this->rubroRepository->findOneByNombreSimilar($palabra);
            if ($rubro) {
                return $rubro;
            }
        }
        return null;
    }

    /**
     * Busca un subrubro que coincida con alguna de las palabras clave.
     */
    private function buscarSubrubroPorPalabras(array $palabras): ?\App\Entity\Subrubro
    {
        // Suponiendo que tienes un SubrubroRepository inyectado
        foreach ($palabras as $palabra) {
            $subrubro = $this->subrubroRepository->findOneByNombreSimilar($palabra);
            if ($subrubro) {
                return $subrubro;
            }
        }
        return null;
    }
} 