<?php

namespace App\Repository\Mssql;

use Doctrine\Persistence\ManagerRegistry;

class ArticuloMssqlRepository
{
    private ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    private function getMssqlEntityManager()
    {
        return $this->registry->getManager('mssql');
    }

    /**
     * Devuelve todos los artículos desde MSSQL como arrays asociativos
     */
    public function getAllArticulos(): array
    {
        $conn = $this->getMssqlEntityManager()->getConnection();
        $sql = "
            SELECT
                [Articulos].[Codigo]*1 as codigo,
                LTRIM(RTRIM([Articulos].[Nombre])) as detalle,
                LTRIM(RTRIM([Atributos_ArticulosxArticulo].Valor)) as detalle_web,
                LTRIM(RTRIM([Articulos].[Presentacion])) as modelo,
                [Articulos].Activo as estado,
                LTRIM(RTRIM(PADRE.Codigo)) as codigo_rubro,
                LTRIM(RTRIM(PADRE.Nombre)) as rubro,
                LTRIM(RTRIM(HIJO.Codigo)) as codigo_subrubro,
                LTRIM(RTRIM(HIJO.Nombre)) as subrubro,
                LTRIM(RTRIM([Marcas_Articulos].[Codigo])) as codigo_marca,
                LTRIM(RTRIM([Marcas_Articulos].[Nombre])) as marca,
                LTRIM(RTRIM(Articulos.Alicuota_IVA)) as impuesto,
                LTRIM(RTRIM(TablaListasPrecios.[Precio 400])) as precio_400,
                LTRIM(RTRIM(TablaListasPrecios.[VENTA])) as precio_lista,
                 -- Nuevo campo: ashArticulo
                LOWER(CONVERT(VARCHAR(40), HASHBYTES('SHA1',
                    ISNULL(LTRIM(RTRIM([Articulos].[Nombre])), '') + '|' +
                    ISNULL(LTRIM(RTRIM([Marcas_Articulos].[Codigo])), '') + '|' +
                    ISNULL(LTRIM(RTRIM([Articulos].[Presentacion])), '') + '|' +
                    ISNULL(LTRIM(RTRIM([Atributos_ArticulosxArticulo].Valor)), '') + '|' +
                    ISNULL(LTRIM(RTRIM(Articulos.Alicuota_IVA)), '') + '|' +
                    ISNULL(LTRIM(RTRIM(TablaListasPrecios.[VENTA])), '') + '|' +
                    ISNULL(LTRIM(RTRIM(TablaListasPrecios.[Precio 400])), '') + '|' +
                    ISNULL(CAST([Articulos].Activo AS VARCHAR), '') + '|' +
                    ISNULL(LTRIM(RTRIM(HIJO.Codigo)), '')
                ), 2)) AS hashArticulo
            FROM [Ciardi].[dbo].[Articulos]
            INNER JOIN [Marcas_Articulos] ON Articulos.ID_Marca_Articulo = Marcas_Articulos.ID_Marca_Articulo
            INNER JOIN [Clasificaciones_Articulos] HIJO ON Articulos.ID_Clasificacion_Articulo = HIJO.ID_Clasificacion_Articulo
            INNER JOIN [Clasificaciones_Articulos] PADRE ON HIJO.ID_Clasificacion_Articulo_Padre = PADRE.ID_Clasificacion_Articulo
            INNER JOIN [Atributos_ArticulosxArticulo] ON Articulos.ID_Articulo = Atributos_ArticulosxArticulo.ID_Articulo and Atributos_ArticulosxArticulo.ID_Atributo_Articulo = 'nombre_web'
            INNER JOIN (
                SELECT
                    max(CASE WHEN ID_Articulo != '1' THEN PreciosxArticulos.ID_Articulo END) ID_Articulo,
                    max(CASE WHEN ID_Lista_Precios = '1' THEN PreciosxArticulos.Precio_VentaSIVA END) VENTA,
                    max(CASE WHEN ID_Lista_Precios = '5' THEN PreciosxArticulos.Precio_VentaSIVA END) [Precio 400]
                FROM PreciosxArticulos
                GROUP BY PreciosxArticulos.ID_Articulo
            ) AS TablaListasPrecios ON TablaListasPrecios.ID_Articulo = Articulos.ID_Articulo
            ORDER BY Articulos.Codigo ASC
        ";
        $stmt = $conn->executeQuery($sql);
        $resultados = $stmt->fetchAllAssociative();

        return $resultados;
    }

    /**
     * Genera un hash global de sincronización para un artículo MSSQL
     */
    public static function generarHashArticulo(array $articulo): string
    {
        if (isset($articulo['hashArticulo'])) {
            return $articulo['hashArticulo'];
        }
        return null;
    }

    /**
     * Obtiene solo los artículos que han cambiado respecto a los existentes
     */
    public function getArticulosModificados(array $hashesExistentes): array
    {
        // Extraer solo los códigos y hashes
        $mapaCodigos = [];
        foreach ($hashesExistentes as $item) {
            $mapaCodigos[$item['codigo']] = $item['hash'];
        }
        
        // Obtener todos los artículos
        $todosArticulos = $this->getAllArticulos();
        
        // Filtrar solo los que han cambiado
        return array_filter($todosArticulos, function($articulo) use ($mapaCodigos) {
            // Artículos nuevos (no existen en MySQL)
            if (!isset($mapaCodigos[$articulo['codigo']])) {
                return true;
            }
            
            // Artículos que han cambiado (hash diferente)
            return $mapaCodigos[$articulo['codigo']] !== $articulo['hashArticulo'];
        });
    }
} 