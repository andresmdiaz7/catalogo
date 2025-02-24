<?php

namespace App\Service;

use App\Entity\Articulo;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
    private $session;

    public function __construct(RequestStack $requestStack)
    {
        $this->session = $requestStack->getSession();
    }

    public function add(Articulo $articulo, int $cantidad = 0): void
    {
        $cart = $this->session->get('cart', []);
        $codigo = $articulo->getCodigo();

        if (!isset($cart[$codigo])) {
            $cart[$codigo] = [
                'codigo' => $codigo,
                'detalle' => $articulo->getDetalle(),
                'marca' => $articulo->getMarca(),
                'modelo' => $articulo->getModelo(), 
                'precioLista' => $articulo->getprecioLista(),
                'cantidad' => 0
            ];
        }
        
        $cart[$codigo]['cantidad'] += $cantidad;
        
        $this->session->set('cart', $cart);
    }

    public function remove(Articulo $articulo): void
    {
        $cart = $this->session->get('cart', []);
        $codigo = $articulo->getCodigo();


        if (isset($cart[$codigo])) {
            unset($cart[$codigo]);
            $this->session->set('cart', $cart);
        }

    }

    public function clear(): void
    {
        $this->session->remove('cart');
    }

    public function getItems(): array
    {
        return $this->session->get('cart', []);
    }

    public function getTotal(): float
    {
        $total = 0;
        foreach ($this->getItems() as $item) {
            $total += $item['precioLista'] * $item['cantidad'];
        }
        return $total;

    }
}
