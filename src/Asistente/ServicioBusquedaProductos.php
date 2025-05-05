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
    // Palabras a excluir de las búsquedas (normalmente palabras comunes)
    private array $palabrasExcluir = ['de', 'la', 'el', 'los', 'las', 'para', 'con', 'por', 'y', 'o', 'a'];
    
    // Prefijos comunes a excluir (para evitar que "portalámpara" coincida con "lámpara")
    private array $prefijosExcluir = ['porta', 'anti', 'semi', 'pre', 'post', 'mini', 'micro', 'macro', 'super', 'ultra'];
    
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
        // Separar la consulta en palabras clave y normalizar
        $consulta = $this->normalizarTexto($consulta);
        $palabras = array_filter(explode(' ', trim($consulta)));
        $palabras = $this->filtrarPalabrasSignificativas($palabras);
        
        if (empty($palabras)) {
            return [];
        }
        
        // Primera búsqueda: usando todas las palabras en modo OR
        $resultados = $this->buscarProductos($palabras, false, true);
        
        // Si no hay suficientes resultados, probar con búsqueda más flexible
        if (count($resultados) < 3) {
            // Buscar con palabras raíz (quitar sufijos comunes)
            $palabrasRaiz = $this->obtenerPalabrasRaiz($palabras);
            
            // Combinar resultados con la primera búsqueda
            $resultadosExtras = $this->buscarProductos($palabrasRaiz, true, false);
            
            foreach ($resultadosExtras as $producto) {
                $encontrado = false;
                foreach ($resultados as $existente) {
                    if ($existente->getCodigo() === $producto->getCodigo()) {
                        $encontrado = true;
                        break;
                    }
                }
                
                if (!$encontrado) {
                    $resultados[] = $producto;
                    // Limitar a 10 resultados
                    if (count($resultados) >= 10) {
                        break;
                    }
                }
            }
        }
        
        return $resultados;
    }
    
    /**
     * Busca productos con criterios específicos
     */
    private function buscarProductos(array $palabras, bool $busquedaFlexible = false, bool $incluirMarca = true): array
    {
        // Buscar coincidencias en rubros y subrubros
        $rubroEncontrado = $this->buscarRubroPorPalabras($palabras);
        $subrubroEncontrado = $this->buscarSubrubroPorPalabras($palabras);
        
        $qb = $this->articuloRepository->createQueryBuilder('a')
            ->leftJoin('a.marca', 'm')
            ->leftJoin('a.subrubro', 'sr')
            ->leftJoin('sr.rubro', 'r')
            ->leftJoin('a.archivos', 'aa')
            ->leftJoin('aa.archivo', 'arc')
            ->andWhere('a.habilitadoWeb = :habilitadoWeb')
            ->andWhere('a.habilitadoGestion = :habilitadoGestion')
            ->andWhere('a.precioLista > 0')
            ->andWhere('EXISTS (
                SELECT 1 FROM App\Entity\ArticuloArchivo aa2
                JOIN aa2.archivo arc2
                WHERE aa2.articulo = a
                AND arc2.tipoMime LIKE :tipoImagen
            )')
            ->setParameter('habilitadoWeb', true)
            ->setParameter('habilitadoGestion', true)
            ->setParameter('tipoImagen', 'image/%')
            ->groupBy('a.codigo')
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

        $whereConditions = [];
        $parameters = [];
        
        // Construir condiciones de búsqueda
        foreach ($palabras as $i => $palabra) {
            // Solo considerar palabras con longitud significativa
            if (strlen($palabra) < 3) continue;
            
            $param = 'p_' . $i;
            
            // Crear condición exclusiva para evitar falsos positivos
            $exclusiones = [];
            foreach ($this->prefijosExcluir as $j => $prefijo) {
                if (strlen($palabra) > strlen($prefijo)) {
                    $paramExcluir = 'excl_' . $i . '_' . $j;
                    $exclusiones[] = "LOWER(a.detalle) NOT LIKE :{$paramExcluir}";
                    $parameters[$paramExcluir] = '%' . $prefijo . $palabra . '%';
                }
            }
            
            $condicion = "LOWER(a.detalle) LIKE :{$param}";
            
            // Agregar búsqueda por marca si se solicita
            if ($incluirMarca) {
                $condicion = "({$condicion} OR (m.nombre IS NOT NULL AND LOWER(m.nombre) LIKE :{$param}))";
            }
            
            // Agregar exclusiones si existen
            if (!empty($exclusiones)) {
                $condicion = "({$condicion} AND (" . implode(' AND ', $exclusiones) . "))";
            }
            
            $whereConditions[] = $condicion;
            $parameters[$param] = '%' . $palabra . '%';
        }
        
        // Si estamos en búsqueda flexible, conectar con OR, de lo contrario usar AND para más precisión
        $operador = $busquedaFlexible ? ' OR ' : ' AND ';
        
        if (!empty($whereConditions)) {
            $qb->andWhere('(' . implode($operador, $whereConditions) . ')');
            foreach ($parameters as $key => $value) {
                $qb->setParameter($key, $value);
            }
        }
        
        return $qb->getQuery()->getResult();
    }
    
    /**
     * Normaliza el texto removiendo acentos y convirtiendo a minúsculas
     */
    private function normalizarTexto(string $texto): string
    {
        $texto = mb_strtolower(trim($texto));
        
        // Reemplazar caracteres acentuados
        $caracteresAcentuados = ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü'];
        $caracteresNormales = ['a', 'e', 'i', 'o', 'u', 'n', 'u'];
        
        return str_replace($caracteresAcentuados, $caracteresNormales, $texto);
    }
    
    /**
     * Filtra palabras significativas eliminando artículos y palabras comunes
     */
    private function filtrarPalabrasSignificativas(array $palabras): array
    {
        return array_filter($palabras, function($palabra) {
            return !in_array($palabra, $this->palabrasExcluir) && strlen($palabra) > 1;
        });
    }
    
    /**
     * Obtiene raíces de palabras eliminando sufijos comunes
     */
    private function obtenerPalabrasRaiz(array $palabras): array
    {
        $sufijosComunes = ['s', 'es', 'a', 'as', 'o', 'os', 'ar', 'er', 'ir', 'mente'];
        $palabrasRaiz = [];
        
        foreach ($palabras as $palabra) {
            // Añadir la palabra original
            $palabrasRaiz[] = $palabra;
            
            // Intentar quitar sufijos para obtener palabras raíz
            foreach ($sufijosComunes as $sufijo) {
                if (strlen($palabra) > strlen($sufijo) + 2) {
                    if (substr($palabra, -strlen($sufijo)) === $sufijo) {
                        $raiz = substr($palabra, 0, -strlen($sufijo));
                        if (strlen($raiz) > 2) {
                            $palabrasRaiz[] = $raiz;
                        }
                    }
                }
            }
        }
        
        return array_unique($palabrasRaiz);
    }

    /**
     * Busca un rubro que coincida con alguna de las palabras clave.
     */
    private function buscarRubroPorPalabras(array $palabras): ?\App\Entity\Rubro
    {
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
        foreach ($palabras as $palabra) {
            $subrubro = $this->subrubroRepository->findOneByNombreSimilar($palabra);
            if ($subrubro) {
                return $subrubro;
            }
        }
        return null;
    }
} 