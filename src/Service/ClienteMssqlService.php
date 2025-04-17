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
     * Obtiene la conexión a la base de datos MSSQL
     */
    private function getMssqlConnection()
    {
        return $this->doctrine->getConnection('mssql');
    }
    
    /**
     * Buscar un cliente por código en la base de datos MSSQL
     */
    public function buscarClientePorCodigo(string $codigo): ?array
    {
        try {
            $conn = $this->getMssqlConnection();
            
            // Consulta SQL original para referencia durante debug
            $sql = "
                SELECT 
                Clientes.codigo as codigo,
                Clientes.nombre as razonSocial,
                Clientes.cuit as cuit,
                Clientes.domicilio as direccion,
                Localidades.nombre as localidadNombre,
	            Localidades.ID_Provincia as provincia_id,
                Clientes.telefono as telefono,
                Clientes.email as email
                FROM Clientes
                INNER JOIN Localidades ON Clientes.ID_LOCALIDAD = Localidades.ID_Localidad
                WHERE Clientes.codigo = :codigo
            ";
            
            // Debug: Imprimir consulta con parámetros reemplazados
            $stmt = $conn->prepare($sql);
            $stmt->bindValue('codigo', $codigo);
            
            error_log("SQL: " . $sql);
            
            $result = $stmt->executeQuery();
            
            // Debug: Verificar si hay resultados
            $cliente = $result->fetchAssociative();
            
            
            // Debug: Verificar si hay cliente
            if ($cliente === false) {
                return null;
            }
            
            return $cliente;
        } catch (\Exception $e) {
            error_log("Error en consulta MSSQL: " . $e->getMessage());
            $this->logError('Error consultando cliente MSSQL', $e);
            return null;
        }
    }
    
    /**
     * Obtiene el monto pendiente de cobro en cuenta corriente para un cliente
     */
    public function getDeudaCuentaCorriente(int $codigo): float
    {
        try {
            $conn = $this->getMssqlConnection();
            
            $sql = "
                SELECT SUM(Pendiente) as total
                FROM Comprobantes_Ventas
                WHERE CODIGO = :codigo 
                AND Tipo_Operacion = :tipoOperacion 
                AND Pendiente <> 0
            ";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindValue('codigo', $codigo);
            $stmt->bindValue('tipoOperacion', 'Cta.Cte.');
            $result = $stmt->executeQuery();
            
            $data = $result->fetchAssociative();
            return $data['total'] ? (float)$data['total'] : 0.0;
        } catch (\Exception $e) {
            $this->logError('Error consultando deuda cuenta corriente', $e);
            return 0.0;
        }
    }
    
    /**
     * Obtiene todos los comprobantes pendientes de un cliente
     */
    public function getComprobantesPendientes(int $codigo): array
    {
        try {
            $conn = $this->getMssqlConnection();
            
            $sql = "
                SELECT 
                    Numero_Comprobante as numeroComprobante,
                    Fecha_Comprobante as fechaComprobante,
                    Fecha_Vencimiento as fechaVencimiento,
                    Total as total,
                    Pendiente as pendiente,
                    Tipo_Operacion as tipoOperacion
                FROM Comprobantes_Ventas
                WHERE CODIGO = :codigo 
                AND Pendiente <> 0
                ORDER BY Fecha_Comprobante DESC
            ";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindValue('codigo', $codigo);
            $result = $stmt->executeQuery();
            
            return $result->fetchAllAssociative();
        } catch (\Exception $e) {
            $this->logError('Error consultando comprobantes pendientes', $e);
            return [];
        }
    }
    
    /**
     * Obtiene un resumen de la cuenta corriente del cliente
     */
    public function getResumenCuentaCorriente(int $codigo): array
    {
        try {
            $totalAdeudado = $this->getDeudaCuentaCorriente($codigo);
            $comprobantes = $this->getComprobantesPendientes($codigo);
            
            return [
                'totalAdeudado' => $totalAdeudado,
                'cantidadComprobantes' => count($comprobantes),
                'comprobantes' => $comprobantes
            ];
        } catch (\Exception $e) {
            $this->logError('Error obteniendo resumen cuenta corriente', $e);
            return [
                'totalAdeudado' => 0,
                'cantidadComprobantes' => 0,
                'comprobantes' => []
            ];
        }
    }
    
    /**
     * Obtiene el historial de compras del cliente
     */
    public function getHistorialCompras(int $codigo, \DateTime $fechaDesde = null, \DateTime $fechaHasta = null): array
    {
        try {
            $conn = $this->getMssqlConnection();
            
            $sql = "
                SELECT 
                    Numero_Comprobante as numeroComprobante,
                    Fecha_Comprobante as fechaComprobante,
                    Total as total,
                    Descripcion as descripcion
                FROM Comprobantes_Ventas
                WHERE CODIGO = :codigo 
            ";
            
            $params = ['codigo' => $codigo];
            
            if ($fechaDesde) {
                $sql .= " AND Fecha_Comprobante >= :fechaDesde";
                $params['fechaDesde'] = $fechaDesde->format('Y-m-d');
            }
            
            if ($fechaHasta) {
                $sql .= " AND Fecha_Comprobante <= :fechaHasta";
                $params['fechaHasta'] = $fechaHasta->format('Y-m-d');
            }
            
            $sql .= " ORDER BY Fecha_Comprobante DESC";
            
            $stmt = $conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $result = $stmt->executeQuery();
            
            return $result->fetchAllAssociative();
        } catch (\Exception $e) {
            $this->logError('Error consultando historial de compras', $e);
            return [];
        }
    }
    
    /**
     * Método centralizado para registrar errores
     */
    private function logError(string $mensaje, \Exception $e): void
    {
        error_log($mensaje . ': ' . $e->getMessage());
        // Aquí podrías agregar lógica adicional de logging, como enviar a Sentry, etc.
    }
}
