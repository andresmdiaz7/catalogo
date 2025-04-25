<?php
// Servicio para gestionar el cliente activo
namespace App\Service;

use App\Entity\Cliente;
use App\Entity\Usuario;
use App\Repository\ClienteRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\SecurityBundle\Security;

class ClienteManager
{
    private $requestStack;
    private $clienteRepository;
    private $security;

    public function __construct(
        RequestStack $requestStack, 
        ClienteRepository $clienteRepository,
        Security $security
    ) {
        $this->requestStack = $requestStack;
        $this->clienteRepository = $clienteRepository;
        $this->security = $security;
    }

    public function getClienteActivo(): ?Cliente
    {
        $session = $this->requestStack->getSession();
        $clienteId = $session->get('cliente_activo_id');
        
        if (!$clienteId) {
            return null;
        }
        
        return $this->clienteRepository->find($clienteId);
    }

    public function setClienteActivo(Cliente $cliente): void
    {
        $session = $this->requestStack->getSession();
        $session->set('cliente_activo_id', $cliente->getId());
        
        // Opcionalmente, puedes guardar algunos datos adicionales para acceso rápido
        $session->set('cliente_activo_data', [
            'id' => $cliente->getId(),
            'razonSocial' => $cliente->getRazonSocial(),
            'codigo' => $cliente->getCodigo()
        ]);
    }

    public function hasClienteActivo(): bool
    {
        $session = $this->requestStack->getSession();
        return $session->has('cliente_activo_id');
    }

    public function clearClienteActivo(): void
    {
        $session = $this->requestStack->getSession();
        $session->remove('cliente_activo_id');
        $session->remove('cliente_activo_data');
    }
    
    /**
     * Configura automáticamente el cliente activo para el usuario actual
     * 
     * @return bool Retorna true si se pudo configurar automáticamente
     */
    public function configurarClienteActivoAutomaticamente(): bool
    {
        $user = $this->security->getUser();
        
        if (!$user instanceof Usuario) {
            return false;
        }
        
        // Si el usuario solo tiene un cliente, configurarlo como activo
        if ($user->hasUnicoCliente()) {
            $this->setClienteActivo($user->getUnicoCliente());
            return true;
        }
        
        return false;
    }
    
    /**
     * Verifica si el cliente activo pertenece al usuario actual
     */
    public function validarClienteActivo(): bool
    {
        $user = $this->security->getUser();
        
        if (!$user instanceof Usuario) {
            return false;
        }
        
        $clienteActivo = $this->getClienteActivo();
        
        if (!$clienteActivo) {
            return false;
        }
        
        // Verificar que el cliente pertenezca al usuario actual
        foreach ($user->getClientes() as $cliente) {
            if ($cliente->getId() === $clienteActivo->getId()) {
                return true;
            }
        }
        
        return false;
    }
}
