<?php

namespace App\Asistente;

/**
 * Interfaz para servicios de OpenAI
 */
interface OpenAIInterface
{
    /**
     * Consulta a la API de OpenAI y devuelve la respuesta.
     */
    public function consultarChatGPT(string $prompt, array $mensajesPrevios = []): string;
} 