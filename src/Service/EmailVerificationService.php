<?php
// src/Service/EmailVerificationService.php

namespace App\Service;

use App\Repository\UsuarioRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class EmailVerificationService
{
    private $usuarioRepository;
    private $entityManager;
    private $logger;

    public function __construct(
        UsuarioRepository $usuarioRepository,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->usuarioRepository = $usuarioRepository;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * Verifica si un email ya está en uso
     * 
     * @param string $email El email a verificar
     * @param int|null $excludeUsuarioId ID de usuario a excluir (útil en ediciones)
     * @return bool true si el email está disponible, false si ya existe
     */
    public function isEmailAvailable(string $email, ?int $excludeUsuarioId = null): bool
    {
        try {
            // Ahora solo necesitamos verificar en la tabla Usuario
            return !$this->usuarioRepository->existeEmail($email, $excludeUsuarioId);
        } catch (\Exception $e) {
            $this->logger->error('Error al verificar email: ' . $e->getMessage());
            // En caso de error, asumimos que el email no está disponible por precaución
            return false;
        }
    }

    /**
     * Normaliza un email para comparaciones consistentes
     * 
     * @param string $email El email a normalizar
     * @return string El email normalizado
     */
    public function normalizeEmail(string $email): string
    {
        // Convertir a minúsculas y eliminar espacios
        return strtolower(trim($email));
    }

    /**
     * Verifica si un cliente tiene asociado un usuario
     * 
     * @param Cliente $cliente
     * @return bool
     */
    public function clienteHasUsuario($cliente): bool
    {
        return $cliente->getUsuario() !== null;
    }
}
