<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * Muestra el formulario de login
     */
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        /**
         * Si el usuario está autenticado, redirigir al dashboard correspondiente
         */ 
        if ($this->getUser()) {
            if (in_array('ROLE_CLIENTE', $this->getUser()->getRoles())) {
                return $this->redirectToRoute('app_cliente_dashboard');
            }
            if (in_array('ROLE_ADMIN', $this->getUser()->getRoles())) {
                return $this->redirectToRoute('app_admin_dashboard');
            }
            if (in_array('ROLE_VENDEDOR', $this->getUser()->getRoles())) {
                return $this->redirectToRoute('app_vendedor_dashboard');
            }
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError()
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
} 