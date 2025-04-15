<?php

namespace App\Service;

use Doctrine\Persistence\ManagerRegistry;

class ClienteMssqlService
{
    private $doctrine;
    
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }
    
    /**
     * Buscar un cliente por código en la base de datos MSSQL
     */
    public function buscarClientePorCodigo(int $codigo): ?array
    {
        try {
            $conn = $this->doctrine->getConnection('mssql');
            
            $sql = "
                SELECT 
                Clientes.codigo as codigo,
                Clientes.nombre as razonSocial,
                Clientes.cuit as cuit,
                Clientes.domicilio as direccion,
                Localidades.nombre as localidadNombre,
                Clientes.telefono as telefono,
                Clientes.email as email
                    FROM Clientes
                    INNER JOIN Localidades ON Clientes.ID_LOCALIDAD = Localidades.ID_Localidad
                    WHERE Clientes.codigo = :codigo
            ";
            
            $stmt = $conn->prepare($sql);
            // Usar bindValue en lugar de pasar el array directamente a executeQuery
            $stmt->bindValue('codigo', $codigo);
            $result = $stmt->executeQuery();
            
            $cliente = $result->fetchAssociative();
            
            // Si no hay cliente, devolvemos null
            // Convertir explícitamente false a null para mantener el tipo de retorno
            if ($cliente === false) {
                return null;
            }
            
            return $cliente;
        } catch (\Exception $e) {
            // Log del error para depuración
            error_log('Error consultando cliente MSSQL: ' . $e->getMessage());
            return null;
        }
    }
}
