<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controlador base para el área de administración.
 * 
 * Esta clase abstracta proporciona funcionalidad común para todos los controladores
 * del área de administración del sistema. Implementa:
 * 
 * - Control de acceso mediante el rol ROLE_ADMIN
 * - Métodos para mostrar mensajes flash al usuario
 * - Herencia de la funcionalidad base de controladores de Symfony
 * 
 * @package App\Controller\Admin
 * @see AbstractController
 */

#[IsGranted('ROLE_ADMIN')]
abstract class AdminController extends AbstractController
{
    protected function addSuccessFlash(string $message): void
    {
        $this->addFlash('success', $message);
    }

    protected function addErrorFlash(string $message): void
    {
        $this->addFlash('error', $message);
    }

    protected function addWarningFlash(string $message): void
    {
        $this->addFlash('warning', $message);
    }

    protected function addInfoFlash(string $message): void
    {
        $this->addFlash('info', $message);
    }
} 