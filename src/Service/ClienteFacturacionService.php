<?php

namespace App\Service;

use Doctrine\Persistence\ManagerRegistry;

class ClienteFacturacionService
{
    private $doctrine;
    
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }
    
    /**
     * Obtiene el monto pendiente de cobro en cuenta corriente para un cliente
     * utilizando directamente la conexión MSSQL
     */
    public function getDeudaCuentaCorriente(int $codigo): float
    {
        try {
            // Obtenemos explícitamente la conexión MSSQL
            $conn = $this->doctrine->getConnection('mssql');
            
            $sql = "
                SELECT SUM(Pendiente) as total
                FROM Comprobantes_Ventas
                WHERE CODIGO = :codigo 
                AND Tipo_Operacion = :tipoOperacion 
                AND Pendiente <> 0
            ";
            
            $stmt = $conn->prepare($sql);
            $result = $stmt->executeQuery([
                'codigo' => $codigo,
                'tipoOperacion' => 'Cta.Cte.'
            ]);
            
            $data = $result->fetchAssociative();
            return $data['total'] ? (float)$data['total'] : 0.0;
        } catch (\Exception $e) {
            // Log del error para depuración
            error_log('Error en consulta MSSQL: ' . $e->getMessage());
            return 0.0;
        }
    }
}
