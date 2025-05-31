<?php

namespace App\Asistente;

use App\Asistente\ServicioConversacionAsistente;
use App\Service\ClienteManager;
use App\Service\CarritoManager;
use App\Repository\ArticuloRepository;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Servicio núcleo del asistente virtual Tito
 * Coordina todas las funcionalidades del asistente incluyendo contexto de navegación,
 * gestión de carrito y persistencia de historial de conversaciones
 */
class ServicioAsistenteCore
{
    public function __construct(
        private ServicioConversacionAsistente $servicioConversacion,
        private ClienteManager $clienteManager,
        private CarritoManager $carritoManager,
        private ArticuloRepository $articuloRepository,
        private RequestStack $requestStack
    ) {}

    /**
     * Procesa una consulta del usuario con contexto completo
     */
    public function procesarConsultaCompleta(
        string $mensaje,
        ?int $usuarioId = null,
        array $historial = [],
        ?string $urlActual = null
    ): array {
        // Obtener contexto de la navegación actual
        $contextoUsuario = $this->construirContextoUsuario($urlActual);
        
        // Obtener historial en formato correcto para OpenAI
        $historialOpenAI = $this->obtenerHistorialParaOpenAI();
        
        // Procesar la consulta con el servicio de conversación
        $respuesta = $this->servicioConversacion->procesarConsulta(
            $mensaje,
            $usuarioId,
            $historialOpenAI,
            $contextoUsuario
        );
        
        // Enriquecer la respuesta con acciones adicionales si es necesario
        return $this->enriquecerRespuesta($respuesta, $mensaje);
    }

    /**
     * Agrega un producto al carrito desde el asistente
     */
    public function agregarProductoAlCarrito(string $codigoArticulo, int $cantidad = 1): array
    {
        try {
            $articulo = $this->articuloRepository->findOneBy(['codigo' => $codigoArticulo]);
            
            if (!$articulo) {
                return [
                    'exito' => false,
                    'mensaje' => 'No encontré el producto con código: ' . $codigoArticulo,
                    'tipo' => 'error'
                ];
            }

            $this->carritoManager->agregarArticulo($articulo, $cantidad);
            
            $nombreProducto = $articulo->getDetalleWeb() ?: $articulo->getDetalle();
            $mensaje = "✅ He agregado {$cantidad} unidad(es) de \"{$nombreProducto}\" a tu carrito.";
            
            if ($cantidad > 1) {
                $mensaje .= " ¿Necesitas algo más?";
            } else {
                $mensaje .= " ¿Te gustaría agregar algo más o ver el carrito?";
            }

            return [
                'exito' => true,
                'mensaje' => $mensaje,
                'tipo' => 'carrito_actualizado',
                'producto_agregado' => [
                    'codigo' => $articulo->getCodigo(),
                    'detalle' => $nombreProducto,
                    'cantidad' => $cantidad
                ]
            ];
        } catch (\Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Hubo un problema al agregar el producto al carrito. ¿Podrías intentar nuevamente?',
                'tipo' => 'error'
            ];
        }
    }

    /**
     * Obtiene el estado actual del carrito
     */
    public function obtenerEstadoCarrito(): array
    {
        try {
            $carrito = $this->carritoManager->obtenerCarritoActivo();
            
            if (!$carrito || $carrito->getItems()->isEmpty()) {
                return [
                    'vacio' => true,
                    'mensaje' => 'Tu carrito está vacío. ¿Te ayudo a encontrar algunos productos?',
                    'cantidad_items' => 0,
                    'total' => 0
                ];
            }

            $items = [];
            $total = 0;
            
            foreach ($carrito->getItems() as $item) {
                $subtotal = $item->getTotal();
                $total += $subtotal;
                
                $items[] = [
                    'codigo' => $item->getArticulo()->getCodigo(),
                    'detalle' => $item->getArticulo()->getDetalleWeb() ?: $item->getArticulo()->getDetalle(),
                    'cantidad' => $item->getCantidad(),
                    'precio_unitario' => (float)$item->getPrecioUnitario(),
                    'subtotal' => $subtotal
                ];
            }

            return [
                'vacio' => false,
                'cantidad_items' => count($items),
                'items' => $items,
                'total' => $total,
                'mensaje' => sprintf('Tienes %d producto(s) en tu carrito por un total de $%s', 
                    count($items), 
                    number_format($total, 2, ',', '.')
                )
            ];
        } catch (\Exception $e) {
            return [
                'vacio' => true,
                'mensaje' => 'No pude acceder a tu carrito en este momento.',
                'cantidad_items' => 0,
                'total' => 0
            ];
        }
    }

    /**
     * Construye el contexto del usuario basado en su navegación actual
     */
    private function construirContextoUsuario(?string $urlActual = null): array
    {
        $contexto = [];
        
        // Obtener URL actual si no se proporcionó
        if (!$urlActual) {
            $request = $this->requestStack->getCurrentRequest();
            $urlActual = $request ? $request->getPathInfo() : null;
        }
        
        if ($urlActual) {
            $contexto['url_actual'] = $urlActual;
            
            // Detectar contexto específico basado en la URL
            if (preg_match('/\/articulo\/([^\/]+)/', $urlActual, $matches)) {
                $codigoArticulo = $matches[1];
                $articulo = $this->articuloRepository->findOneBy(['codigo' => $codigoArticulo]);
                
                if ($articulo) {
                    $contexto['producto_actual'] = [
                        'codigo' => $articulo->getCodigo(),
                        'detalle' => $articulo->getDetalleWeb() ?: $articulo->getDetalle(),
                        'marca' => $articulo->getMarca() ? $articulo->getMarca()->getNombre() : null,
                        'rubro' => $articulo->getSubrubro() ? $articulo->getSubrubro()->getRubro()->getNombre() : null,
                        'subrubro' => $articulo->getSubrubro() ? $articulo->getSubrubro()->getNombre() : null
                    ];
                }
            } elseif (preg_match('/\/catalogo\/rubro\/([^\/]+)/', $urlActual, $matches)) {
                $contexto['navegando_rubro'] = $matches[1];
            } elseif (preg_match('/\/catalogo\/subrubro\/([^\/]+)/', $urlActual, $matches)) {
                $contexto['navegando_subrubro'] = $matches[1];
            } elseif (strpos($urlActual, '/carrito') !== false) {
                $contexto['en_carrito'] = true;
            }
        }
        
        return $contexto;
    }

    /**
     * Enriquece la respuesta con acciones adicionales
     */
    private function enriquecerRespuesta(array $respuesta, string $mensajeOriginal): array
    {
        // Agregar sugerencias inteligentes basadas en el contexto
        $respuesta['sugerencias'] = $this->generarSugerencias($respuesta, $mensajeOriginal);
        
        // Agregar acciones disponibles
        $respuesta['acciones_disponibles'] = $this->obtenerAccionesDisponibles($respuesta);
        
        return $respuesta;
    }

    /**
     * Genera sugerencias inteligentes para el usuario
     */
    private function generarSugerencias(array $respuesta, string $mensajeOriginal): array
    {
        $sugerencias = [];
        
        // Si encontró productos, sugerir acciones
        if ($respuesta['tipo'] === 'busqueda_productos' && !empty($respuesta['productos'])) {
            $sugerencias[] = [
                'texto' => 'Ver detalles del primer producto',
                'accion' => 'ver_detalle',
                'parametros' => ['codigo' => $respuesta['productos'][0]->codigo]
            ];
            
            $sugerencias[] = [
                'texto' => 'Agregar al carrito',
                'accion' => 'agregar_carrito',
                'parametros' => ['codigo' => $respuesta['productos'][0]->codigo]
            ];
        }
        
        // Si no encontró resultados, sugerir alternativas
        if ($respuesta['tipo'] === 'sin_resultados') {
            $sugerencias[] = [
                'texto' => 'Ver catálogo completo',
                'accion' => 'ver_catalogo'
            ];
            
            $sugerencias[] = [
                'texto' => 'Contactar a un asesor',
                'accion' => 'contactar_asesor'
            ];
        }
        
        return $sugerencias;
    }

    /**
     * Obtiene las acciones disponibles según el contexto
     */
    private function obtenerAccionesDisponibles(array $respuesta): array
    {
        $acciones = [
            'buscar_producto' => 'Buscar productos',
            'ver_carrito' => 'Ver carrito',
            'obtener_sucursal' => 'Información de sucursales',
            'contactar_area' => 'Contactar área especializada'
        ];
        
        // Agregar acciones específicas según el tipo de respuesta
        if ($respuesta['tipo'] === 'busqueda_productos') {
            $acciones['agregar_carrito'] = 'Agregar al carrito';
        }
        
        return $acciones;
    }

    /**
     * Guarda el historial de conversación (para implementar persistencia)
     */
    public function guardarHistorialConversacion(int $usuarioId, string $mensaje, string $respuesta): void
    {
        // TODO: Implementar persistencia de historial en base de datos
        // Por ahora solo en sesión
        $session = $this->requestStack->getSession();
        $historial = $session->get('asistente_historial', []);
        
        // Agregar mensaje del usuario
        $historial[] = [
            'role' => 'user',
            'content' => $mensaje,
            'timestamp' => new \DateTime(),
            'usuario_id' => $usuarioId
        ];
        
        // Agregar respuesta del asistente
        $historial[] = [
            'role' => 'assistant',
            'content' => $respuesta,
            'timestamp' => new \DateTime(),
            'usuario_id' => null
        ];
        
        // Mantener solo los últimos 20 intercambios (40 mensajes)
        if (count($historial) > 40) {
            $historial = array_slice($historial, -40);
        }
        
        $session->set('asistente_historial', $historial);
    }

    /**
     * Obtiene el historial de conversación
     */
    public function obtenerHistorialConversacion(): array
    {
        $session = $this->requestStack->getSession();
        return $session->get('asistente_historial', []);
    }

    /**
     * Obtiene el historial en formato OpenAI (solo role y content)
     */
    public function obtenerHistorialParaOpenAI(): array
    {
        $historial = $this->obtenerHistorialConversacion();
        $historialOpenAI = [];
        
        foreach ($historial as $mensaje) {
            if (isset($mensaje['role']) && isset($mensaje['content'])) {
                $historialOpenAI[] = [
                    'role' => $mensaje['role'],
                    'content' => $mensaje['content']
                ];
            }
        }
        
        return $historialOpenAI;
    }
} 