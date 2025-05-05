<?php

namespace App\Asistente;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ServicioOpenAIChat
{
    private HttpClientInterface $httpClient;
    private string $apiKey;
    private string $modelo;

    public function __construct(
        HttpClientInterface $httpClient,
        string $apiKey,
        string $modelo = 'gpt-4'
    ) {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->modelo = $modelo;
    }

    /**
     * Envía un mensaje a la API de OpenAI y devuelve la respuesta.
     */
    public function consultarChatGPT(string $prompt, array $mensajesPrevios = []): string
    {
        // Endpoint actualizado para la API de OpenAI
        $endpoint = 'https://api.openai.com/v1/chat/completions';

        // Estructura de mensajes para el modelo de chat
        $mensajes = [];
        foreach ($mensajesPrevios as $msg) {
            $mensajes[] = ['role' => $msg['role'], 'content' => $msg['content']];
        }
        $mensajes[] = ['role' => 'user', 'content' => $prompt];

        $response = $this->httpClient->request('POST', $endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => $this->modelo,
                'messages' => $mensajes,
                'temperature' => 0.7,
                'max_tokens' => 800,
                'top_p' => 1,
                'frequency_penalty' => 0,
                'presence_penalty' => 0,
            ],
        ]);

        $data = $response->toArray(false);

        // Verificar la respuesta y manejar posibles errores
        if (isset($data['error'])) {
            throw new \RuntimeException('Error de OpenAI: ' . ($data['error']['message'] ?? 'Error desconocido'));
        }

        return $data['choices'][0]['message']['content'] ?? '';
    }
} 