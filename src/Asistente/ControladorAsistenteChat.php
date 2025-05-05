<?php

namespace App\Asistente;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controlador principal para el chat del asistente.
 * Recibe las consultas del usuario y responde con productos o información relevante.
 */
class ControladorAsistenteChat extends AbstractController
{
    #[Route('/asistente/chat', name: 'asistente_chat', methods: ['POST'])]
    public function chat(Request $request, ServicioConversacionAsistente $servicioConversacion): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $mensaje = $data['mensaje'] ?? '';
        $usuarioId = $data['usuario_id'] ?? null;
        $historial = $data['historial'] ?? [];
        $contextoUsuario = $data['contexto_usuario'] ?? [];

        // Obtener el usuario autenticado y el cliente activo
        $usuario = $this->getUser();
        if ($usuario && method_exists($usuario, 'getClienteActivo')) {
            $cliente = $usuario->getClienteActivo();
            if ($cliente) {
                if (method_exists($cliente, 'getNombre')) {
                    $contextoUsuario['nombre_cliente'] = $cliente->getNombre();
                }
                if (method_exists($cliente, 'getId')) {
                    $contextoUsuario['id_cliente'] = $cliente->getId();
                }
                // Puedes agregar aquí otros datos relevantes del cliente
            }
        }

        // Procesar la consulta con el servicio de conversación
        $respuesta = $servicioConversacion->procesarConsulta($mensaje, $usuarioId, $historial, $contextoUsuario);

        // Estructura clara para el frontend del chat
        return $this->json([
            'respuesta' => $respuesta['respuesta'],
            'productos' => $respuesta['productos'],
        ]);
    }
} 