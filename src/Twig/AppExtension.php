<?php

namespace App\Twig;

use App\Entity\EstadoPedido;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('estado_pedido', [$this, 'getEstadoPedido']),
        ];
    }

    public function getEstadoPedido(): array
    {
        return [
            'PENDIENTE' => EstadoPedido::PENDIENTE,
            'REVISADO' => EstadoPedido::REVISADO,
        ];
    }
} 