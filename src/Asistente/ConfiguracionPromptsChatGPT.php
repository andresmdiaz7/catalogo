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
        return self::$config[$clave] ?? $porDefecto ?? "Mensaje no definido.";
    }

    // Puedes agregar más métodos para otros prompts o mensajes frecuentes.
} 