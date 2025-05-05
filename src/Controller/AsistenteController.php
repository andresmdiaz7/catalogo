<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Asistente\ServicioConversacionAsistente;


/**
 * Controlador para mostrar la interfaz del chat del asistente.
 */
class AsistenteController extends AbstractController
{
    #[Route('/asistente/chat-ui', name: 'asistente_chat_ui')]
    public function chat(): Response
    {
        return $this->render('asistente/chat.html.twig');
    }

    #[Route('/asistente/chat', name: 'asistente_chat', methods: ['POST'])]
    public function chatPost(Request $request, ServicioConversacionAsistente $servicioConversacion): JsonResponse
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
            }
        }

        $respuesta = $servicioConversacion->procesarConsulta($mensaje, $usuarioId, $historial, $contextoUsuario);

        return $this->json([
            'respuesta' => $respuesta['respuesta'],
            'productos' => $respuesta['productos'],
        ]);
    }
} 