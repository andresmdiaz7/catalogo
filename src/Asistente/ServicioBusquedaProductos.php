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
        // Primero verificar si la consulta contiene un código específico
        $productoPorCodigo = $this->buscarPorCodigo($consulta);
        if (!empty($productoPorCodigo)) {
            return $productoPorCodigo;
        }
        
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
     * Busca un producto específico por código
     */
    public function buscarPorCodigo(string $consulta): array
    {
        // Extraer códigos potenciales de la consulta
        $codigosPotenciales = $this->extraerCodigosDeLaConsulta($consulta);
        
        if (empty($codigosPotenciales)) {
            return [];
        }
        
        $productos = [];
        
        foreach ($codigosPotenciales as $codigo) {
            $qb = $this->articuloRepository->createQueryBuilder('a')
                ->leftJoin('a.marca', 'm')
                ->leftJoin('a.subrubro', 'sr')
                ->leftJoin('sr.rubro', 'r')
                ->where('a.codigo = :codigo')
                ->andWhere('a.habilitadoWeb = :habilitadoWeb')
                ->andWhere('a.habilitadoGestion = :habilitadoGestion')
                ->andWhere('a.precioLista > 0')
                ->setParameter('codigo', $codigo)
                ->setParameter('habilitadoWeb', true)
                ->setParameter('habilitadoGestion', true)
                ->setMaxResults(1);
            
            $producto = $qb->getQuery()->getOneOrNullResult();
            
            if ($producto) {
                $productos[] = $producto;
                break; // Si encontramos uno por código exacto, es suficiente
            }
        }
        
        return $productos;
    }
    
    /**
     * Busca productos por múltiples criterios: código, detalle, detalleweb, modelo
     */
    public function buscarPorCriteriosMultiples(string $consulta): array
    {
        // Primero intentar búsqueda por código exacto
        $resultadosCodigo = $this->buscarPorCodigo($consulta);
        if (!empty($resultadosCodigo)) {
            return $resultadosCodigo;
        }
        
        // Normalizar la consulta
        $consulta = $this->normalizarTexto($consulta);
        $palabras = array_filter(explode(' ', trim($consulta)));
        $palabras = $this->filtrarPalabrasSignificativas($palabras);
        
        if (empty($palabras)) {
            return [];
        }
        
        // Búsqueda en múltiples campos
        $qb = $this->articuloRepository->createQueryBuilder('a')
            ->leftJoin('a.marca', 'm')
            ->leftJoin('a.subrubro', 'sr')
            ->leftJoin('sr.rubro', 'r')
            ->leftJoin('a.archivos', 'aa')
            ->leftJoin('aa.archivo', 'arc')
            ->andWhere('a.habilitadoWeb = :habilitadoWeb')
            ->andWhere('a.habilitadoGestion = :habilitadoGestion')
            ->andWhere('a.precioLista > 0')
            ->setParameter('habilitadoWeb', true)
            ->setParameter('habilitadoGestion', true)
            ->groupBy('a.codigo')
            ->setMaxResults(10);

        $whereConditions = [];
        $parameters = [];
        
        foreach ($palabras as $i => $palabra) {
            if (strlen($palabra) < 2) continue;
            
            $param = 'p_' . $i;
            $parameters[$param] = '%' . $palabra . '%';
            
            // Buscar en código, detalle, detalleWeb, modelo y marca
            $condicion = "(
                LOWER(a.codigo) LIKE :{$param} OR
                LOWER(a.detalle) LIKE :{$param} OR
                LOWER(a.detalleWeb) LIKE :{$param} OR
                LOWER(a.modelo) LIKE :{$param} OR
                (m.nombre IS NOT NULL AND LOWER(m.nombre) LIKE :{$param})
            )";
            
            $whereConditions[] = $condicion;
        }
        
        if (!empty($whereConditions)) {
            // Usar AND para ser más específico
            $qb->andWhere('(' . implode(' AND ', $whereConditions) . ')');
            foreach ($parameters as $key => $value) {
                $qb->setParameter($key, $value);
            }
        }
        
        return $qb->getQuery()->getResult();
    }
    
    /**
     * Extrae códigos potenciales de una consulta de texto
     */
    private function extraerCodigosDeLaConsulta(string $consulta): array
    {
        $codigosPotenciales = [];
        
        // Patrones comunes para códigos de productos
        $patrones = [
            '/\b(\d{6,12})\b/',      // Números de 6 a 12 dígitos
            '/\b([A-Z]{1,3}\d{4,10})\b/i',  // Letras seguidas de números
            '/\b(\d{1,4}[A-Z]{1,3}\d{1,8})\b/i', // Números-letras-números
            '/codigo[:\s]*([A-Z0-9\-]{4,15})/i', // Después de "codigo:"
            '/\b([A-Z0-9\-]{6,15})\b/i'  // Códigos alfanuméricos con guiones
        ];
        
        foreach ($patrones as $patron) {
            if (preg_match_all($patron, $consulta, $matches)) {
                $codigosPotenciales = array_merge($codigosPotenciales, $matches[1]);
            }
        }
        
        // Limpiar y validar códigos
        $codigosLimpios = [];
        foreach ($codigosPotenciales as $codigo) {
            $codigo = trim($codigo);
            // Códigos deben tener al menos 4 caracteres y máximo 15
            if (strlen($codigo) >= 4 && strlen($codigo) <= 15) {
                $codigosLimpios[] = $codigo;
            }
        }
        
        return array_unique($codigosLimpios);
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