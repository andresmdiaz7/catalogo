<?php
// Carga el entorno de Symfony
require __DIR__.'/vendor/autoload.php';

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Dotenv\Dotenv;

// Cargar variables de entorno
$dotenv = new Dotenv();
$dotenv->loadEnv(__DIR__.'/.env');
if (file_exists(__DIR__.'/.env.local')) {
    $dotenv->load(__DIR__.'/.env.local');
}

// Obtener la clave API
$apiKey = $_ENV['OPENAI_API_KEY'] ?? getenv('OPENAI_API_KEY');
// Forzar el uso de gpt-3.5-turbo ya que gpt-4 no está disponible
$model = 'gpt-3.5-turbo';

echo "Usando clave API: " . substr($apiKey, 0, 10) . "..." . PHP_EOL;
echo "Usando modelo: " . $model . PHP_EOL;

// Crear cliente HTTP
$httpClient = HttpClient::create();

try {
    // Hacer la solicitud a OpenAI
    $response = $httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ],
        'json' => [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => '¡Hola! ¿Cómo estás?']
            ],
            'temperature' => 0.7,
            'max_tokens' => 100,
        ],
    ]);

    // Obtener la respuesta
    $statusCode = $response->getStatusCode();
    $content = $response->getContent(false);
    $data = json_decode($content, true);

    echo "Código de estado: " . $statusCode . PHP_EOL;
    echo "Respuesta: " . PHP_EOL;
    print_r($data);

    if (isset($data['choices'][0]['message']['content'])) {
        echo "Mensaje recibido: " . $data['choices'][0]['message']['content'] . PHP_EOL;
    } elseif (isset($data['error'])) {
        echo "Error recibido: " . $data['error']['message'] . PHP_EOL;
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
} 