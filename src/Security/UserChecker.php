<?php

namespace App\Security;

use App\Entity\Usuario;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof Usuario) {
            return;
        }

        if (!$user->isActivo()) {
            // Mensaje personalizado que se mostrará al usuario
            throw new CustomUserMessageAuthenticationException(
                'Su cuenta ha sido desactivada. Por favor contacte al administrador.'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // Aquí se pueden realizar verificaciones adicionales después de la autenticación si es necesario
    }
} 