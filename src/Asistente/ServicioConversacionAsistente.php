<?php

namespace App\Asistente;

use App\Asistente\ServicioBusquedaProductos;
use App\Asistente\DTO\RespuestaProductoDTO;
use App\Asistente\ConfiguracionPromptsChatGPT;
use App\Asistente\ServicioOpenAIChat;

/**
 * Servicio principal para manejar la lógica conversacional del asistente.
 * Analiza la intención del usuario y coordina la búsqueda de productos o respuestas.
 */
class ServicioConversacionAsistente
{
    public function __construct(
        private ServicioBusquedaProductos $servicioBusquedaProductos,
        private ServicioOpenAIChat $servicioOpenAIChat
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
        $promptBase = ConfiguracionPromptsChatGPT::obtenerMensaje('prompt_base');
        $prompt = $promptBase . "\n";

        // Agregar contexto del usuario (incluye nombre_cliente si está disponible)
        if (!empty($contextoUsuario)) {
            $prompt .= "Contexto usuario: " . json_encode($contextoUsuario, JSON_UNESCAPED_UNICODE) . "\n";
        }

        // Agregar historial de conversación
        foreach ($historial as $turno) {
            $prompt .= "{$turno['role']}: {$turno['content']}\n";
        }

        $prompt .= "Usuario: " . $mensaje;

        $respuestaGPT = $this->servicioOpenAIChat->consultarChatGPT($prompt);
        
        // Si usas Monolog, puedes hacer:
        // $this->logger->info('Respuesta IA: ' . $respuestaGPT);

        // --- Nueva lógica para interpretar JSON ---
        if ($this->esJson($respuestaGPT)) {
            $data = json_decode($respuestaGPT, true);
            if ($data['accion'] === 'buscar_producto') {
                $palabrasClave = $data['parametros']['palabras_clave'] ?? '';
                $productos = $this->servicioBusquedaProductos->buscarPorDetalleYMarca($palabrasClave);

                if (count($productos) > 0) {
                    return [
                        'respuesta' => ConfiguracionPromptsChatGPT::obtenerMensaje('productos_encontrados', 'Estos son los productos que encontré:'),
                        'productos' => array_map(fn($producto) => RespuestaProductoDTO::fromEntity($producto), $productos),
                    ];
                } else {
                    return [
                        'respuesta' => ConfiguracionPromptsChatGPT::obtenerMensaje('sin_resultados', 'No encontré productos que coincidan con tu búsqueda.'),
                        'productos' => [],
                    ];
                }
            }
            // Aquí puedes agregar más acciones según el JSON recibido
        }

        // Lógica anterior para compatibilidad
        if (stripos(trim($respuestaGPT), 'BUSCAR_PRODUCTO:') === 0) {
            $palabrasClave = trim(substr($respuestaGPT, strlen('BUSCAR_PRODUCTO:')));
            $productos = $this->servicioBusquedaProductos->buscarPorDetalleYMarca($palabrasClave);

            if (count($productos) > 0) {
                return [
                    'respuesta' => ConfiguracionPromptsChatGPT::obtenerMensaje('productos_encontrados', 'Estos son los productos que encontré:'),
                    'productos' => array_map(fn($producto) => RespuestaProductoDTO::fromEntity($producto), $productos),
                ];
            } else {
                return [
                    'respuesta' => ConfiguracionPromptsChatGPT::obtenerMensaje('sin_resultados', 'No encontré productos que coincidan con tu búsqueda.'),
                    'productos' => [],
                ];
            }
        } else if (stripos(trim($respuestaGPT), 'CONTACTO_AREA:') === 0) {
            $area = trim(substr($respuestaGPT, strlen('CONTACTO_AREA:')));
            return [
                'respuesta' => "Aquí tienes los datos de contacto del área: <strong>{$area}</strong>.",
                'productos' => [],
            ];
        } else {
            // Solo mostrar el texto de ChatGPT, no buscar productos
            return [
                'respuesta' => $respuestaGPT,
                'productos' => [],
            ];
        }
    }

    // Función auxiliar para detectar JSON
    private function esJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
} 