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
     * Muestra el formulario de login
     */
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->security->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('app_post_login_redirect');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
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

    #[Route('/post-login-redirect', name: 'app_post_login_redirect')]
    public function postLoginRedirect(): Response
    {
        $user = $this->security->getUser();
        
        if (!$user instanceof Usuario) {
            return $this->redirectToRoute('app_login');
        }
        
        // Actualizar último acceso
        $user->setUltimoAcceso(new \DateTime());
        
        // Redirigir según el tipo de usuario
        $tipoUsuario = $user->getTipoUsuario();
        
        if (!$tipoUsuario) {
            return $this->redirectToRoute('app_login');
        }

        switch ($tipoUsuario->getCodigo()) {
            case 'admin':
                return $this->redirectToRoute('app_admin_dashboard');
            
            case 'cliente':
                // Buscar la entidad Cliente asociada a este usuario
                $clientes = $this->clienteRepository->findBy(['usuario' => $user]);
                
                if (count($clientes) > 1) {
                    // Redirigir a la selección de cliente
                    return $this->redirectToRoute('app_cliente_seleccionar');
                } elseif (count($clientes) === 1) {
                    // Establecer el cliente activo en sesión
                    $this->get('session')->set('cliente_activo_id', $clientes[0]->getId());
                    return $this->redirectToRoute('app_cliente_dashboard');
                } else {
                    return $this->redirectToRoute('app_login', [
                        'error' => 'No hay clientes asociados a este usuario'
                    ]);
                }
            
            case 'vendedor':
                return $this->redirectToRoute('app_vendedor_dashboard');
            
            case 'responsable_logistica':
                return $this->redirectToRoute('app_logistica_dashboard');
            
            default:
                return $this->redirectToRoute('app_login');
        }
    }
} 