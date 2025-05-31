<?php

namespace App\Asistente;

use App\Asistente\ServicioBusquedaProductos;
use App\Asistente\DTO\RespuestaProductoDTO;
use App\Asistente\ConfiguracionPromptsChatGPT;
use App\Asistente\ServicioOpenAIChat;
use App\Service\ArticuloPrecioService;
use App\Service\ClienteManager;

/**
 * Servicio principal para manejar la lógica conversacional del asistente.
 * Analiza la intención del usuario y coordina la búsqueda de productos o respuestas.
 */
class ServicioConversacionAsistente
{
    public function __construct(
        private ServicioBusquedaProductos $servicioBusquedaProductos,
        private ServicioOpenAIChat $servicioOpenAIChat,
        private ArticuloPrecioService $articuloPrecioService,
        private ClienteManager $clienteManager
    ) {}

    /**
     * Procesa la consulta del usuario y devuelve la respuesta estructurada.
     */
    public function procesarConsulta(
        string $mensaje,
        ?int $usuarioId,
        array $historial = [],
        array $contextoUsuario = []
    ): array
    {
        // Obtener información del cliente actual
        $cliente = $this->clienteManager->getClienteActivo();
        $nombreCliente = $cliente ? $cliente->getRazonSocial() : null;

        // Construir el prompt base con la nueva estructura
        $promptBase = ConfiguracionPromptsChatGPT::obtenerPromptBase();
        $prompt = $promptBase . "\n\n";

        // Agregar información del asistente
        $configAsistente = ConfiguracionPromptsChatGPT::obtenerConfigAsistente();
        $prompt .= "INFORMACIÓN DEL ASISTENTE:\n";
        $prompt .= "- Nombre: " . ($configAsistente['nombre'] ?? 'Tito') . "\n";
        $prompt .= "- Empresa: " . ($configAsistente['empresa'] ?? 'Ciardi Hnos') . "\n";
        $prompt .= "- Especialidad: " . ($configAsistente['especialidad'] ?? 'Materiales Eléctricos') . "\n\n";

        // Agregar contexto del usuario
        if (!empty($contextoUsuario)) {
            $prompt .= "CONTEXTO USUARIO:\n";
            if ($nombreCliente) {
                $prompt .= "- Cliente: {$nombreCliente}\n";
            }
            
            // Agregar contexto de URL si está disponible
            if (isset($contextoUsuario['url_actual'])) {
                $prompt .= "- Página actual: {$contextoUsuario['url_actual']}\n";
            }
            
            if (isset($contextoUsuario['producto_actual'])) {
                $prompt .= "- Viendo producto: {$contextoUsuario['producto_actual']}\n";
            }
            
            $prompt .= "\n";
        }

        // Agregar información de sucursales y áreas si es relevante
        $prompt .= "INFORMACIÓN DISPONIBLE:\n";
        $prompt .= "- Sucursales: " . count(ConfiguracionPromptsChatGPT::obtenerSucursales()) . " ubicaciones\n";
        $prompt .= "- Áreas especializadas: " . implode(', ', array_keys(ConfiguracionPromptsChatGPT::obtenerAreasEspecializadas())) . "\n\n";

        // El historial ya viene en formato OpenAI desde ServicioAsistenteCore
        $prompt .= "Cliente: " . $mensaje;

        // Consultar a ChatGPT con el historial en formato correcto
        $respuestaGPT = $this->servicioOpenAIChat->consultarChatGPT($prompt, $historial);

        // Procesar la respuesta
        return $this->procesarRespuestaGPT($respuestaGPT, $cliente);
    }

    /**
     * Procesa la respuesta de GPT y ejecuta acciones correspondientes
     */
    private function procesarRespuestaGPT(string $respuestaGPT, $cliente = null): array
    {
        // Log temporal para debugging
        error_log("ASISTENTE DEBUG - Respuesta completa de GPT: " . $respuestaGPT);
        
        // Verificar si hay un JSON embebido en la respuesta
        if (preg_match('/```json\s*(\{.*?\})\s*```/s', $respuestaGPT, $matches)) {
            $jsonString = $matches[1];
            error_log("ASISTENTE DEBUG - JSON encontrado: " . $jsonString);
            
            $data = json_decode($jsonString, true);
            
            if ($data && isset($data['accion'])) {
                error_log("ASISTENTE DEBUG - Acción detectada: " . $data['accion']);
                
                // Extraer el texto sin el JSON
                $textoLimpio = preg_replace('/```json\s*\{.*?\}\s*```/s', '', $respuestaGPT);
                $textoLimpio = trim($textoLimpio);
                
                // Procesar la acción
                $resultado = $this->ejecutarAccion($data, $cliente);
                
                // Combinar el texto limpio con el resultado de la acción
                if ($resultado['tipo'] === 'busqueda_productos' && !empty($resultado['productos'])) {
                    return [
                        'respuesta' => $textoLimpio,
                        'productos' => $resultado['productos'],
                        'tipo' => $resultado['tipo']
                    ];
                } else {
                    return [
                        'respuesta' => $textoLimpio . "\n\n" . $resultado['respuesta'],
                        'productos' => $resultado['productos'] ?? [],
                        'tipo' => $resultado['tipo']
                    ];
                }
            }
        } else {
            error_log("ASISTENTE DEBUG - No se encontró JSON embebido");
        }
        
        // Verificar si es una respuesta JSON completa (sin texto adicional)
        if ($this->esJson($respuestaGPT)) {
            $data = json_decode($respuestaGPT, true);
            return $this->ejecutarAccion($data, $cliente);
        }

        // Verificar comandos de compatibilidad
        if (stripos(trim($respuestaGPT), 'BUSCAR_PRODUCTO:') === 0) {
            $palabrasClave = trim(substr($respuestaGPT, strlen('BUSCAR_PRODUCTO:')));
            return $this->ejecutarBusquedaProducto(['palabras_clave' => $palabrasClave], $cliente);
        }

        if (stripos(trim($respuestaGPT), 'CONTACTO_AREA:') === 0) {
            $area = trim(substr($respuestaGPT, strlen('CONTACTO_AREA:')));
            return $this->ejecutarConsultaArea(['area' => $area]);
        }

        // Respuesta de texto normal
        return $this->respuestaTexto($respuestaGPT);
    }

    /**
     * Ejecuta una acción basada en los datos JSON
     */
    private function ejecutarAccion(array $data, $cliente = null): array
    {
        switch ($data['accion'] ?? '') {
            case 'buscar_producto':
                return $this->ejecutarBusquedaProducto($data['parametros'] ?? [], $cliente);
            
            case 'obtener_precio':
                return $this->ejecutarConsultaPrecio($data['parametros'] ?? [], $cliente);
            
            case 'obtener_sucursal':
                return $this->ejecutarConsultaSucursal($data['parametros'] ?? []);
            
            case 'contactar_area':
                return $this->ejecutarConsultaArea($data['parametros'] ?? []);
            
            default:
                return $this->respuestaTexto('No pude procesar esa acción.');
        }
    }

    /**
     * Ejecuta búsqueda de productos
     */
    private function ejecutarBusquedaProducto(array $parametros, $cliente = null): array
    {
        $palabrasClave = $parametros['palabras_clave'] ?? '';
        
        // Log temporal para debugging
        error_log("ASISTENTE DEBUG - Buscando productos con: " . $palabrasClave);
        
        // Usar el método mejorado que busca por código, detalle, detalleweb, modelo y marca
        $productos = $this->servicioBusquedaProductos->buscarPorCriteriosMultiples($palabrasClave);
        
        // Si no encuentra nada con el método mejorado, usar el método tradicional como fallback
        if (empty($productos)) {
            $productos = $this->servicioBusquedaProductos->buscarPorDetalleYMarca($palabrasClave);
        }
        
        error_log("ASISTENTE DEBUG - Productos encontrados: " . count($productos));

        if (count($productos) > 0) {
            $productosDTO = [];
            foreach ($productos as $producto) {
                $precioPersonalizado = null;
                
                // Calcular precio personalizado si hay cliente
                if ($cliente) {
                    $precioPersonalizado = $this->articuloPrecioService->getPrecioFinal($producto);
                }
                
                $productosDTO[] = RespuestaProductoDTO::fromEntity($producto, $precioPersonalizado);
            }

            $mensajeProductos = count($productos) === 1 
                ? "Encontré 1 producto que coincide con tu búsqueda:" 
                : "Encontré " . count($productos) . " productos que coinciden con tu búsqueda:";

            return [
                'respuesta' => $mensajeProductos,
                'productos' => $productosDTO,
                'tipo' => 'busqueda_productos'
            ];
        } else {
            return [
                'respuesta' => ConfiguracionPromptsChatGPT::obtenerMensaje('busqueda', 'sin_resultados', 'No encontré productos que coincidan con tu búsqueda. ¿Podrías darme más detalles o verificar el código del producto?'),
                'productos' => [],
                'tipo' => 'sin_resultados'
            ];
        }
    }

    /**
     * Ejecuta consulta de precio específico
     */
    private function ejecutarConsultaPrecio(array $parametros, $cliente = null): array
    {
        $codigo = $parametros['codigo'] ?? '';
        $palabrasClave = $parametros['palabras_clave'] ?? '';
        
        // Si hay código específico, buscar por código usando las palabras clave
        if ($codigo) {
            $productos = $this->servicioBusquedaProductos->buscarPorCriteriosMultiples($codigo);
        } elseif ($palabrasClave) {
            // Si hay palabras clave, buscar productos
            $productos = $this->servicioBusquedaProductos->buscarPorCriteriosMultiples($palabrasClave);
        } else {
            return $this->respuestaTexto("Por favor, especifica un código de producto o palabras clave para buscar el precio.");
        }
        
        if (count($productos) === 0) {
            return $this->respuestaTexto("No encontré ningún producto con esa información. ¿Podrías verificar el código o darme más detalles?");
        }
        
        if (count($productos) === 1) {
            $producto = $productos[0];
            $precioPersonalizado = null;
            
            // Calcular precio personalizado si hay cliente
            if ($cliente) {
                $precioPersonalizado = $this->articuloPrecioService->getPrecioFinal($producto);
            }
            
            $nombreProducto = $producto->getDetalleWeb() ?: $producto->getDetalle();
            $respuesta = "💰 **Precio de " . $nombreProducto . "**\n\n";
            $respuesta .= "🏷️ **Código:** " . $producto->getCodigo() . "\n";
            if ($producto->getMarca()) {
                $respuesta .= "🔖 **Marca:** " . $producto->getMarca()->getNombre() . "\n";
            }
            
            if ($precioPersonalizado) {
                $respuesta .= "💵 **Tu precio:** $" . number_format($precioPersonalizado, 2, ',', '.') . "\n";
                $respuesta .= "\n¿Te gustaría agregarlo al carrito?";
            } else {
                $respuesta .= "💵 **Precio lista:** $" . number_format($producto->getPrecioLista(), 2, ',', '.') . "\n";
                $respuesta .= "\n*Para obtener tu precio personalizado, por favor contacta a tu vendedor.*";
            }
            
            return $this->respuestaTexto($respuesta);
        }
        
        // Si hay múltiples productos, mostrar lista con precios
        $respuesta = "💰 **Precios encontrados:**\n\n";
        
        foreach (array_slice($productos, 0, 5) as $producto) { // Mostrar máximo 5
            $precioPersonalizado = null;
            if ($cliente) {
                $precioPersonalizado = $this->articuloPrecioService->getPrecioFinal($producto);
            }
            
            $nombreProducto = $producto->getDetalleWeb() ?: $producto->getDetalle();
            $respuesta .= "🏷️ **" . $producto->getCodigo() . "** - " . $nombreProducto . "\n";
            
            if ($precioPersonalizado) {
                $respuesta .= "💵 Tu precio: $" . number_format($precioPersonalizado, 2, ',', '.') . "\n";
            } else {
                $respuesta .= "💵 Precio lista: $" . number_format($producto->getPrecioLista(), 2, ',', '.') . "\n";
            }
            $respuesta .= "\n";
        }
        
        if (count($productos) > 5) {
            $respuesta .= "*(Mostrando los primeros 5 resultados de " . count($productos) . " encontrados)*\n\n";
        }
        
        $respuesta .= "¿Te interesa alguno en particular?";
        
        return $this->respuestaTexto($respuesta);
    }

    /**
     * Ejecuta consulta de sucursal
     */
    private function ejecutarConsultaSucursal(array $parametros): array
    {
        $codigoSucursal = $parametros['sucursal'] ?? '';
        
        if ($codigoSucursal) {
            $sucursal = ConfiguracionPromptsChatGPT::obtenerSucursal($codigoSucursal);
            if ($sucursal) {
                $respuesta = "🏢 **{$sucursal['nombre']}**\n\n";
                $respuesta .= "📍 **Dirección:** {$sucursal['direccion']}\n";
                $respuesta .= "📞 **Teléfono:** {$sucursal['telefono']}\n";
                if (isset($sucursal['whatsapp'])) {
                    $respuesta .= "💬 **WhatsApp:** {$sucursal['whatsapp']}\n";
                }
                if (isset($sucursal['email'])) {
                    $respuesta .= "📧 **Email:** {$sucursal['email']}\n";
                }
                $respuesta .= "🕒 **Horarios:** {$sucursal['horarios']['lunes_viernes']}\n";
                if (isset($sucursal['horarios']['sabados'])) {
                    $respuesta .= "🕒 **Sábados:** {$sucursal['horarios']['sabados']}\n";
                }
                
                if (isset($sucursal['servicios']) && is_array($sucursal['servicios'])) {
                    $respuesta .= "🛠️ **Servicios:** " . implode(', ', $sucursal['servicios']);
                }
                
                return $this->respuestaTexto($respuesta);
            }
        }
        
        // Mostrar todas las sucursales con formato mejorado
        $sucursales = ConfiguracionPromptsChatGPT::obtenerSucursales();
        $respuesta = "🏢 **Nuestras Sucursales**\n\n";
        
        foreach ($sucursales as $codigo => $sucursal) {
            $respuesta .= "🏪 **{$sucursal['nombre']}**\n";
            $respuesta .= "📍 {$sucursal['direccion']}\n";
            $respuesta .= "📞 {$sucursal['telefono']}";
            if (isset($sucursal['whatsapp'])) {
                $respuesta .= " | 💬 WhatsApp: {$sucursal['whatsapp']}";
            }
            $respuesta .= "\n";
            if (isset($sucursal['horarios']['lunes_viernes'])) {
                $respuesta .= "🕒 L-V: {$sucursal['horarios']['lunes_viernes']}";
                if (isset($sucursal['horarios']['sabados'])) {
                    $respuesta .= " | Sáb: {$sucursal['horarios']['sabados']}";
                }
                $respuesta .= "\n";
            }
            $respuesta .= "\n";
        }
        
        $respuesta .= "¿Te gustaría más información sobre alguna sucursal en particular?";
        
        return $this->respuestaTexto($respuesta);
    }

    /**
     * Ejecuta consulta de área especializada
     */
    private function ejecutarConsultaArea(array $parametros): array
    {
        $area = $parametros['area'] ?? '';
        $areaInfo = ConfiguracionPromptsChatGPT::obtenerAreaEspecializada($area);
        
        if ($areaInfo) {
            $respuesta = "👨‍💼 **{$areaInfo['nombre']}**\n\n";
            $respuesta .= "👤 **Responsable:** {$areaInfo['responsable']}\n";
            $respuesta .= "📧 **Email:** {$areaInfo['email']}\n";
            $respuesta .= "📞 **Teléfono:** {$areaInfo['telefono']}\n";
            if (isset($areaInfo['whatsapp'])) {
                $respuesta .= "💬 **WhatsApp:** {$areaInfo['whatsapp']}\n";
            }
            
            if (isset($areaInfo['especialidades']) && is_array($areaInfo['especialidades'])) {
                $respuesta .= "🔧 **Especialidades:**\n";
                foreach ($areaInfo['especialidades'] as $especialidad) {
                    $respuesta .= "• {$especialidad}\n";
                }
            }
            
            return $this->respuestaTexto($respuesta);
        }
        
        // Mostrar todas las áreas disponibles
        $areas = ConfiguracionPromptsChatGPT::obtenerAreasEspecializadas();
        $respuesta = "👥 **Nuestras Áreas Especializadas**\n\n";
        
        foreach ($areas as $nombreArea => $info) {
            $respuesta .= "🏢 **{$info['nombre']}**\n";
            $respuesta .= "👤 {$info['responsable']} | 📞 {$info['telefono']}\n";
            if (isset($info['especialidades']) && is_array($info['especialidades'])) {
                $respuesta .= "🔧 " . implode(', ', $info['especialidades']) . "\n";
            }
            $respuesta .= "\n";
        }
        
        $respuesta .= "¿Te gustaría contactar con alguna área en particular?";
        
        return $this->respuestaTexto($respuesta);
    }

    /**
     * Genera respuesta de solo texto
     */
    private function respuestaTexto(string $texto): array
    {
        return [
            'respuesta' => $texto,
            'productos' => [],
            'tipo' => 'texto'
        ];
    }

    /**
     * Verifica si una cadena es JSON válido
     */
    private function esJson(string $string): bool
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
} 