<?php

namespace App\Controller\Cliente;

use App\Entity\Usuario;
use App\Service\ClienteManager;
use App\Repository\ClienteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/cliente')]
#[IsGranted('ROLE_CLIENTE')]
class ClienteController extends AbstractController
{
    #[Route('/seleccionar', name: 'app_cliente_seleccionar')]
    public function seleccionar(Request $request, ClienteManager $clienteManager, EntityManagerInterface $entityManager): Response
    {
        /** @var Usuario $user */
        $user = $this->getUser();
        
        // Verificar si el usuario tiene clientes
        if (!$user->hasClientes()) {
            $this->addFlash('error', 'No hay clientes asociados a este usuario');
            return $this->redirectToRoute('app_logout');
        }
        
        // Si solo hay un cliente, configurarlo automáticamente y redirigir
        if ($user->hasUnicoCliente()) {
            $cliente = $user->getUnicoCliente();
            $clienteManager->setClienteActivo($cliente);
            // Incrementar cantidad de ingresos y actualizar última visita
            $cliente->incrementarCantidadIngresos();
            $cliente->setUltimaVisita(new \DateTime());
            $entityManager->flush();
            return $this->redirectToRoute('app_cliente_dashboard');
        }
        
        // Para cambiar de cliente activo
        if ($request->isMethod('POST')) {
            $clienteId = $request->request->get('cliente_id');
            
            foreach ($user->getClientes() as $cliente) {
                if ($cliente->getId() == $clienteId) {
                    $clienteManager->setClienteActivo($cliente);
                    // Incrementar cantidad de ingresos y actualizar última visita
                    $cliente->incrementarCantidadIngresos();
                    $cliente->setUltimaVisita(new \DateTime());
                    $entityManager->flush();
                    $this->addFlash('success', 'Cliente seleccionado: ' . $cliente->getRazonSocial());
                    return $this->redirectToRoute('app_cliente_dashboard');
                }
            }
            
            $this->addFlash('error', 'Cliente no válido');
        }
        
        return $this->render('cliente/seleccionar.html.twig', [
            'clientes' => $user->getClientes()
        ]);
    }
    
    #[Route('/dashboard', name: 'app_cliente_dashboard')]
    public function dashboard(ClienteManager $clienteManager): Response
    {
        // Verificar si hay un cliente activo
        if (!$clienteManager->hasClienteActivo()) {
            // Intentar configurar automáticamente
            if (!$clienteManager->configurarClienteActivoAutomaticamente()) {
                return $this->redirectToRoute('app_cliente_seleccionar');
            }
        }
        
        // Verificar que el cliente activo pertenece al usuario actual
        if (!$clienteManager->validarClienteActivo()) {
            $this->addFlash('error', 'El cliente seleccionado no es válido');
            return $this->redirectToRoute('app_cliente_seleccionar');
        }
        
        $clienteActivo = $clienteManager->getClienteActivo();
        
        return $this->render('cliente/dashboard/index.html.twig', [
            'cliente' => $clienteActivo
        ]);
    }
    
    #[Route('/cambiar-cliente', name: 'app_cliente_cambiar')]
    public function cambiarCliente(ClienteManager $clienteManager): Response
    {
        // Limpiar el cliente activo y redirigir a la selección
        $clienteManager->clearClienteActivo();
        return $this->redirectToRoute('app_cliente_seleccionar');
    }
}
