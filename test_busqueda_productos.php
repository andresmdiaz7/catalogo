<?php
// Script temporal para probar la búsqueda de productos
require_once 'vendor/autoload.php';

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Dotenv\Dotenv;
use App\Kernel;
use App\Entity\Articulo;
use App\Entity\Rubro;
use App\Entity\Subrubro;

// Cargar variables de entorno
$dotenv = new Dotenv();
$dotenv->load('.env');

// Crear kernel y obtener container
$kernel = new Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();

// Obtener servicios
$entityManager = $container->get('doctrine.orm.entity_manager');
$articuloRepository = $entityManager->getRepository(Articulo::class);

echo "=== PROBANDO BÚSQUEDA DE PRODUCTOS ===\n";

// Crear instancia del servicio de búsqueda
$rubroRepository = $entityManager->getRepository(Rubro::class);
$subrubroRepository = $entityManager->getRepository(Subrubro::class);

$servicioBusqueda = new \App\Asistente\ServicioBusquedaProductos(
    $articuloRepository,
    $rubroRepository,
    $subrubroRepository
);

// Códigos de prueba
$codigosPrueba = [
    '991004860',
    'lampara led bulbo 9w',
    'sixelectric',
    'precio de una lampara led bulbo de 9w marca sixelectric',
    'el codigo es 991004860'
];

foreach ($codigosPrueba as $consulta) {
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "CONSULTA: '$consulta'\n";
    echo str_repeat("-", 50) . "\n";
    
    // Probar búsqueda por criterios múltiples
    echo "🔍 Búsqueda por criterios múltiples:\n";
    $productos = $servicioBusqueda->buscarPorCriteriosMultiples($consulta);
    echo "Productos encontrados: " . count($productos) . "\n";
    
    foreach ($productos as $producto) {
        echo "  • Código: " . $producto->getCodigo() . "\n";
        echo "    Detalle: " . $producto->getDetalle() . "\n";
        if ($producto->getDetalleWeb()) {
            echo "    Detalle Web: " . $producto->getDetalleWeb() . "\n";
        }
        if ($producto->getModelo()) {
            echo "    Modelo: " . $producto->getModelo() . "\n";
        }
        if ($producto->getMarca()) {
            echo "    Marca: " . $producto->getMarca()->getNombre() . "\n";
        }
        echo "    Precio: $" . number_format($producto->getPrecioLista(), 2) . "\n";
        echo "\n";
    }
    
    // Si no encuentra nada, probar búsqueda tradicional
    if (empty($productos)) {
        echo "🔍 Búsqueda tradicional (fallback):\n";
        $productos = $servicioBusqueda->buscarPorDetalleYMarca($consulta);
        echo "Productos encontrados: " . count($productos) . "\n";
        
        foreach ($productos as $producto) {
            echo "  • Código: " . $producto->getCodigo() . "\n";
            echo "    Detalle: " . $producto->getDetalle() . "\n";
            if ($producto->getMarca()) {
                echo "    Marca: " . $producto->getMarca()->getNombre() . "\n";
            }
            echo "    Precio: $" . number_format($producto->getPrecioLista(), 2) . "\n";
            echo "\n";
        }
    }
    
    // Probar extracción de códigos
    $reflection = new ReflectionClass($servicioBusqueda);
    $method = $reflection->getMethod('extraerCodigosDeLaConsulta');
    $method->setAccessible(true);
    $codigos = $method->invoke($servicioBusqueda, $consulta);
    
    if (!empty($codigos)) {
        echo "🔢 Códigos extraídos: " . implode(', ', $codigos) . "\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "=== VERIFICANDO CÓDIGO ESPECÍFICO EN BD ===\n";

// Verificar si el código específico existe en la base de datos
$codigo = '991004860';
$qb = $articuloRepository->createQueryBuilder('a')
    ->where('a.codigo = :codigo')
    ->setParameter('codigo', $codigo)
    ->setMaxResults(1);

$producto = $qb->getQuery()->getOneOrNullResult();

if ($producto) {
    echo "✅ PRODUCTO ENCONTRADO DIRECTAMENTE EN BD:\n";
    echo "Código: " . $producto->getCodigo() . "\n";
    echo "Detalle: " . $producto->getDetalle() . "\n";
    echo "Detalle Web: " . ($producto->getDetalleWeb() ?: 'N/A') . "\n";
    echo "Modelo: " . ($producto->getModelo() ?: 'N/A') . "\n";
    echo "Marca: " . ($producto->getMarca() ? $producto->getMarca()->getNombre() : 'N/A') . "\n";
    echo "Precio Lista: $" . number_format($producto->getPrecioLista(), 2) . "\n";
    echo "Habilitado Web: " . ($producto->isHabilitadoWeb() ? 'SI' : 'NO') . "\n";
    echo "Habilitado Gestión: " . ($producto->isHabilitadoGestion() ? 'SI' : 'NO') . "\n";
} else {
    echo "❌ PRODUCTO NO ENCONTRADO EN BD CON CÓDIGO: $codigo\n";
}

echo "\n=== FIN PRUEBA ===\n"; 