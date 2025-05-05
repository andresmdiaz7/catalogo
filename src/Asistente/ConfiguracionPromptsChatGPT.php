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

    public static function obtenerPromptBienvenida(): string
    {
        self::cargarConfig();
        return self::$config['bienvenida'] ?? "¡Hola! Soy el asistente virtual de Ciardi Hnos. ¿En qué puedo ayudarte hoy?";
    }

    public static function obtenerMensaje(string $clave, ?string $porDefecto = null): string
    {
        self::cargarConfig();
        
        // Si la clave es 'prompt_base' o alguna otra clave de nivel principal
        if (isset(self::$config[$clave])) {
            return self::$config[$clave];
        }
        
        // Buscar en la sección 'mensajes'
        if (isset(self::$config['mensajes'][$clave])) {
            return self::$config['mensajes'][$clave];
        }
        
        // Devolver el valor por defecto o "Mensaje no definido"
        return $porDefecto ?? "Mensaje no definido.";
    }

    // Puedes agregar más métodos para otros prompts o mensajes frecuentes.
} 