<?php
namespace App\Entity;

/**
 * Enum que representa los estados posibles de un pedido
 */
enum EstadoPedido: string {
    /**
     * Estado inicial cuando el cliente envía el pedido
     */
    case PENDIENTE = 'PENDIENTE';        // Cliente envió el pedido

    /**
     * Estado cuando el responsable de logística ha revisado el pedido
     */
    case REVISADO = 'REVISADO';          // Logística ya lo vio

    /**
     * Obtiene la etiqueta descriptiva del estado
     */
    public function getLabel(): string
    {
        return match($this) {
            self::PENDIENTE => 'Pendiente',
            self::REVISADO => 'Revisado'
        };
    }

    /**
     * Obtiene la clase CSS para el badge del estado
     */
    public function getBadgeClass(): string
    {
        return match($this) {
            self::PENDIENTE => 'bg-warning text-dark',
            self::REVISADO => 'bg-success text-white'
        };
    }

    /**
     * Obtiene el icono de Bootstrap Icons para el estado
     */
    public function getIcon(): string
    {
        return match($this) {
            self::PENDIENTE => 'bi-clock',
            self::REVISADO => 'bi-check-circle'
        };
    }

    /**
     * Obtiene la descripción detallada del estado
     */
    public function getDescription(): string
    {
        return match($this) {
            self::PENDIENTE => 'El pedido está esperando ser revisado por el equipo de logística',
            self::REVISADO => 'El pedido ha sido revisado por el equipo de logística'
        };
    }
}