<?php

namespace App\Controller;

use App\Entity\Usuario;
use App\Entity\Cliente;
use App\Entity\Vendedor;
use App\Repository\ClienteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Bundle\SecurityBundle\Security;
use App\Service\ClienteManager;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;

class SecurityController extends AbstractController
{
    private $clienteRepository;
    private $security;
    
    public function __construct(
        ClienteRepository $clienteRepository,
        Security $security
    ) {
        $this->clienteRepository = $clienteRepository;
        $this->security = $security;
    }

    /**
     * Muestra el formulario de login y maneja la redirección post-login
     */
    #[Route('/login', name: 'app_login')]
    public function login(
        AuthenticationUtils $authenticationUtils,
        EntityManagerInterface $entityManager,
        ClienteManager $clienteManager
    ): Response {
        // Verificar si el usuario ya está autenticado
        $usuario = $this->security->getUser();
        if ($usuario instanceof Usuario) {
            // Actualizar último acceso
            $usuario->setUltimoAcceso(new \DateTime());
            $entityManager->flush();

            // Verificar roles y redirigir según corresponda
            $tipoUsuario = $usuario->getTipoUsuario();
            if ($tipoUsuario) {
                switch ($tipoUsuario->getCodigo()) {
                    case 'admin':
                        return $this->redirectToRoute('app_admin_panel');
                        
                    case 'cliente':
                        // Verificar si el usuario tiene clientes asociados
                        if ($usuario->hasUnicoCliente()) {
                            // Si solo tiene un cliente, establecerlo como activo automáticamente
                            $cliente = $usuario->getUnicoCliente();
                            $clienteManager->setClienteActivo($cliente);
                            // Incrementar cantidad de ingresos y actualizar última visita
                            $cliente->incrementarCantidadIngresos();
                            $cliente->setUltimaVisita(new \DateTime());
                            $entityManager->flush();
                            return $this->redirectToRoute('app_cliente_panel');
                        } elseif ($usuario->hasMultiplesClientes()) {
                            // Si tiene múltiples clientes, mostrar pantalla de selección
                            return $this->redirectToRoute('app_cliente_seleccionar');
                        } else {
                            $this->addFlash('danger', 'No hay clientes asociados a este usuario');
                            return $this->redirectToRoute('app_login');
                        }
                        
                    case 'vendedor':
                        return $this->redirectToRoute('app_vendedor_panel');
                        
                    case 'responsable_logistica':
                        return $this->redirectToRoute('app_logistica_panel');
                        
                    default:
                        return $this->redirectToRoute('app_login');
                }
            }
        }

        // Obtener error de login si existe
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // Obtener último username ingresado
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }

    /**
     * Cierra la sesión del usuario
     */
    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // El logout es manejado por Symfony Security
    }

    /**
     * Esta ruta ya no es necesaria porque toda la lógica está en el método login
     * Se mantiene por compatibilidad, pero redirige al método login
     */
    #[Route('/post-login-redirect', name: 'app_post_login_redirect')]
    public function postLoginRedirect(): Response
    {
        return $this->redirectToRoute('app_login');
    }
} 