<?php

namespace App\Repository\Mssql;

use App\Entity\Mssql\ComprobanteVenta;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ComprobanteVentaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ComprobanteVenta::class);
    }
    
    // Este método asegura que se use el entity manager de MSSQL
    private function getMssqlEntityManager()
    {
        return $this->getEntityManager('mssql');
    }
    
    /**
     * Obtiene el total pendiente de un cliente en cuenta corriente
     * 
     * @todo Esta funcion no se usa por ahora, ver si la implementamos en el servicio de Mssql
     */
    public function getSumaPendienteCuentaCorriente(string $codigo): float
    {
        // Usando DQL (Doctrine Query Language)
        $query = $this->getMssqlEntityManager()
            ->createQuery(
                'SELECT SUM(c.pendiente) 
                FROM App\Entity\Mssql\ComprobanteVenta c 
                WHERE c.codigo = :codigo 
                AND c.tipoOperacion = :tipoOperacion 
                AND c.pendiente != 0'
            )
            ->setParameter('codigo', $codigo)
            ->setParameter('tipoOperacion', 'Cta.Cte.');
        
        $result = $query->getSingleScalarResult();
        
        return $result ? (float)$result : 0.0;
    }
    
    /**
     * Alternativa usando SQL nativo si DQL da problemas
     * @todo Esta funcion no se usa por ahora, ver si la implementamos en el servicio de Mssql
     */
    public function getSumaPendienteCuentaCorrienteNativo(string $codigo): float
    {
        $conn = $this->getMssqlEntityManager()->getConnection();
        $sql = "
            SELECT SUM(Pendiente) as total
            FROM Comprobantes_Ventas
            WHERE CODIGO = :codigo 
            AND Tipo_Operacion = :tipoOperacion 
            AND Pendiente <> 0
        ";
        
        $result = $conn->executeQuery(
            $sql, 
            [
                'codigo' => $codigo,
                'tipoOperacion' => 'Cta.Cte.'
            ]
        );
        
        $data = $result->fetchAssociative();
        return $data['total'] ? (float)$data['total'] : 0.0;
    }
}
