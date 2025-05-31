<?php

namespace App\Controller;

use App\Asistente\ServicioAsistenteCore;
use App\Asistente\ConfiguracionPromptsChatGPT;
use App\Service\ClienteManager;
use App\Entity\Usuario;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controlador para el asistente virtual Tito
 */
#[Route('/asistente')]
class AsistenteController extends AbstractController
{
    public function __construct(
        private ServicioAsistenteCore $servicioAsistenteCore,
        private ClienteManager $clienteManager
    ) {}

    /**
     * Página principal del asistente (chat dedicado)
     */
    #[Route('/', name: 'app_asistente_chat')]
    #[IsGranted('ROLE_CLIENTE')]
    public function chat(): Response
    {
        // Verificar cliente activo
        if (!$this->clienteManager->hasClienteActivo()) {
            if (!$this->clienteManager->configurarClienteActivoAutomaticamente()) {
                return $this->redirectToRoute('app_cliente_seleccionar');
            }
        }

        $cliente = $this->clienteManager->getClienteActivo();
        $bienvenida = ConfiguracionPromptsChatGPT::obtenerPromptBienvenida($cliente?->getRazonSocial());

        return $this->render('asistente/chat.html.twig', [
            'cliente' => $cliente,
            'mensaje_bienvenida' => $bienvenida,
            'historial' => $this->servicioAsistenteCore->obtenerHistorialConversacion()
        ]);
    }

    /**
     * Endpoint para procesar mensajes del chat
     */
    #[Route('/mensaje', name: 'app_asistente_mensaje', methods: ['POST'])]
    #[IsGranted('ROLE_CLIENTE')]
    public function procesarMensaje(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $mensaje = $data['mensaje'] ?? '';
            $urlActual = $data['url_actual'] ?? null;

            if (empty($mensaje)) {
                return new JsonResponse([
                    'error' => true,
                    'mensaje' => 'No se recibió ningún mensaje'
                ], 400);
            }

            // Obtener usuario e historial
            /** @var Usuario|null $usuario */
            $usuario = $this->getUser();
            $usuarioId = $usuario ? $usuario->getId() : null;
            $historial = $this->servicioAsistenteCore->obtenerHistorialConversacion();

            // Procesar la consulta
            $respuesta = $this->servicioAsistenteCore->procesarConsultaCompleta(
                $mensaje,
                $usuarioId,
                $historial,
                $urlActual
            );

            // Guardar en historial
            if ($usuarioId) {
                $this->servicioAsistenteCore->guardarHistorialConversacion(
                    $usuarioId,
                    $mensaje,
                    $respuesta['respuesta']
                );
            }

            return new JsonResponse([
                'exito' => true,
                'respuesta' => $respuesta['respuesta'],
                'productos' => $respuesta['productos'] ?? [],
                'tipo' => $respuesta['tipo'] ?? 'texto',
                'sugerencias' => $respuesta['sugerencias'] ?? [],
                'acciones_disponibles' => $respuesta['acciones_disponibles'] ?? []
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => true,
                'mensaje' => 'Ocurrió un error procesando tu consulta. Por favor, intenta nuevamente.',
                'detalle' => $e->getMessage() // Solo para desarrollo
            ], 500);
        }
    }

    /**
     * Endpoint para agregar productos al carrito desde el asistente
     */
    #[Route('/agregar-carrito', name: 'app_asistente_agregar_carrito', methods: ['POST'])]
    #[IsGranted('ROLE_CLIENTE')]
    public function agregarAlCarrito(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $codigo = $data['codigo'] ?? '';
            $cantidad = (int)($data['cantidad'] ?? 1);

            if (empty($codigo)) {
                return new JsonResponse([
                    'exito' => false,
                    'mensaje' => 'No se especificó el código del producto'
                ], 400);
            }

            $resultado = $this->servicioAsistenteCore->agregarProductoAlCarrito($codigo, $cantidad);

            return new JsonResponse($resultado);

        } catch (\Exception $e) {
            return new JsonResponse([
                'exito' => false,
                'mensaje' => 'Error al agregar el producto al carrito',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Endpoint para obtener el estado del carrito
     */
    #[Route('/estado-carrito', name: 'app_asistente_estado_carrito', methods: ['GET'])]
    #[IsGranted('ROLE_CLIENTE')]
    public function estadoCarrito(): JsonResponse
    {
        try {
            $estado = $this->servicioAsistenteCore->obtenerEstadoCarrito();
            return new JsonResponse($estado);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => true,
                'mensaje' => 'Error al obtener el estado del carrito',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Endpoint para obtener información de sucursales
     */
    #[Route('/sucursales', name: 'app_asistente_sucursales', methods: ['GET'])]
    public function obtenerSucursales(): JsonResponse
    {
        try {
            $sucursales = ConfiguracionPromptsChatGPT::obtenerSucursales();
            return new JsonResponse([
                'sucursales' => $sucursales
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => true,
                'mensaje' => 'Error al obtener información de sucursales'
            ], 500);
        }
    }

    /**
     * Endpoint para obtener información de áreas especializadas
     */
    #[Route('/areas', name: 'app_asistente_areas', methods: ['GET'])]
    public function obtenerAreas(): JsonResponse
    {
        try {
            $areas = ConfiguracionPromptsChatGPT::obtenerAreasEspecializadas();
            return new JsonResponse([
                'areas' => $areas
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => true,
                'mensaje' => 'Error al obtener información de áreas'
            ], 500);
        }
    }

    /**
     * Endpoint para limpiar el historial de conversación
     */
    #[Route('/limpiar-historial', name: 'app_asistente_limpiar_historial', methods: ['POST'])]
    #[IsGranted('ROLE_CLIENTE')]
    public function limpiarHistorial(Request $request): JsonResponse
    {
        try {
            $session = $request->getSession();
            $session->remove('asistente_historial');

            return new JsonResponse([
                'exito' => true,
                'mensaje' => 'Historial limpiado correctamente'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => true,
                'mensaje' => 'Error al limpiar el historial'
            ], 500);
        }
    }

    /**
     * Widget del asistente para incluir en otras páginas
     */
    #[Route('/widget', name: 'app_asistente_widget')]
    #[IsGranted('ROLE_CLIENTE')]
    public function widget(): Response
    {
        $cliente = $this->clienteManager->getClienteActivo();
        $bienvenida = ConfiguracionPromptsChatGPT::obtenerPromptBienvenida($cliente?->getRazonSocial());

        return $this->render('asistente/widget.html.twig', [
            'cliente' => $cliente,
            'mensaje_bienvenida' => $bienvenida
        ]);
    }
} 