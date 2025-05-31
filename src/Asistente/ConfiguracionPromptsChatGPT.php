<?php

namespace App\Asistente;

use Symfony\Component\Yaml\Yaml;

/**
 * Clase para centralizar los prompts y mensajes base para ChatGPT.
 * Lee los mensajes desde un archivo YAML de configuración.
 */
class ConfiguracionPromptsChatGPT
{
    private static ?array $config = null;

    private static function cargarConfig(): void
    {
        if (self::$config === null) {
            $ruta = __DIR__ . '/../../config/asistente_chatgpt.yaml';
            self::$config = file_exists($ruta) ? Yaml::parseFile($ruta) : [];
        }
    }

    /**
     * Obtiene la configuración del asistente
     */
    public static function obtenerConfigAsistente(): array
    {
        self::cargarConfig();
        return self::$config['asistente'] ?? [];
    }

    /**
     * Obtiene el prompt base del asistente
     */
    public static function obtenerPromptBase(): string
    {
        self::cargarConfig();
        return self::$config['prompt_base'] ?? "Eres Tito, el asistente virtual de Ciardi Hnos.";
    }

    /**
     * Obtiene mensaje de bienvenida personalizado
     */
    public static function obtenerPromptBienvenida(?string $nombreCliente = null): string
    {
        self::cargarConfig();
        
        if ($nombreCliente) {
            $saludo = self::$config['mensajes']['interaccion']['saludo_personalizado'] ?? "¡Hola {cliente}! ¿En qué puedo ayudarte hoy?";
            return str_replace('{cliente}', $nombreCliente, $saludo);
        }
        
        return self::$config['mensajes']['interaccion']['bienvenida'] ?? "¡Hola! Soy Tito, tu asistente virtual de Ciardi Hnos. ¿En qué puedo ayudarte hoy?";
    }

    /**
     * Obtiene información de sucursales
     */
    public static function obtenerSucursales(): array
    {
        self::cargarConfig();
        return self::$config['sucursales'] ?? [];
    }

    /**
     * Obtiene información de una sucursal específica
     */
    public static function obtenerSucursal(string $codigo): ?array
    {
        $sucursales = self::obtenerSucursales();
        return $sucursales[$codigo] ?? null;
    }

    /**
     * Obtiene información de áreas especializadas
     */
    public static function obtenerAreasEspecializadas(): array
    {
        self::cargarConfig();
        return self::$config['areas_especializadas'] ?? [];
    }

    /**
     * Obtiene información de un área específica
     */
    public static function obtenerAreaEspecializada(string $area): ?array
    {
        $areas = self::obtenerAreasEspecializadas();
        return $areas[$area] ?? null;
    }

    /**
     * Obtiene las acciones disponibles del asistente
     */
    public static function obtenerAccionesDisponibles(): array
    {
        self::cargarConfig();
        return self::$config['acciones_disponibles'] ?? [];
    }

    /**
     * Obtiene la configuración de contexto
     */
    public static function obtenerConfigContexto(): array
    {
        self::cargarConfig();
        return self::$config['contexto'] ?? [];
    }

    /**
     * Obtiene ejemplos de conversación por categoría
     */
    public static function obtenerEjemplosConversacion(string $categoria): array
    {
        self::cargarConfig();
        return self::$config['ejemplos_conversacion'][$categoria] ?? [];
    }

    /**
     * Obtiene las iniciativas por tipo
     */
    public static function obtenerIniciativa(string $tipo): ?array
    {
        self::cargarConfig();
        return self::$config['iniciativas'][$tipo] ?? null;
    }

    /**
     * Obtiene un mensaje específico con compatibilidad hacia atrás
     */
    public static function obtenerMensaje(string $claveOCategoria, ?string $clave = null, ?string $porDefecto = null): string
    {
        self::cargarConfig();
        
        // Si se proporcionan dos parámetros, usar la nueva estructura: categoria.clave
        if ($clave !== null) {
            if (isset(self::$config['mensajes'][$claveOCategoria][$clave])) {
                return self::$config['mensajes'][$claveOCategoria][$clave];
            }
            return $porDefecto ?? "Mensaje no definido.";
        }
        
        // Compatibilidad con la estructura antigua (un solo parámetro)
        $rutasCompatibilidad = [
            'prompt_base' => ['prompt_base'],
            'productos_encontrados' => ['mensajes', 'busqueda', 'productos_encontrados'],
            'sin_resultados' => ['mensajes', 'busqueda', 'sin_resultados'],
            'cotizacion_iniciada' => ['mensajes', 'compra', 'cotizacion_iniciada'],
            'bienvenida' => ['mensajes', 'interaccion', 'bienvenida'],
        ];

        if (isset($rutasCompatibilidad[$claveOCategoria])) {
            $ruta = $rutasCompatibilidad[$claveOCategoria];
            $valor = self::$config;
            
            foreach ($ruta as $nivel) {
                if (isset($valor[$nivel])) {
                    $valor = $valor[$nivel];
                } else {
                    return $porDefecto ?? "Mensaje no definido.";
                }
            }
            
            return is_string($valor) ? $valor : ($porDefecto ?? "Mensaje no definido.");
        }
        
        return $porDefecto ?? "Mensaje no definido.";
    }
} 