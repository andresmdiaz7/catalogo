<?php

namespace App\Controller\Cliente;

use App\Entity\Usuario;
use App\Entity\EstadoPedido;
use App\Form\ClientePerfilType;
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
    /**
     * Seleccionar cliente de los que tenga asociados al usuario
     * 
     * @param Request $request
     * @param ClienteManager $clienteManager
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
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
            return $this->redirectToRoute('app_cliente_panel');
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
                    $this->addFlash('success', 'Cliente activo: ' . $cliente->getRazonSocial());
                    return $this->redirectToRoute('app_cliente_panel');
                }
            }
            
            $this->addFlash('error', 'Cliente no válido');
        }
        
        return $this->render('cliente/seleccionar.html.twig', [
            'clientes' => $user->getClientes()
        ]);
    }
    
    
    /**
     * Cambiar cliente
     * 
     * @param ClienteManager $clienteManager
     * @return Response
     */
    #[Route('/cambiar-cliente', name: 'app_cliente_cambiar')]
    public function cambiarCliente(ClienteManager $clienteManager): Response
    {
        // Limpiar el cliente activo y redirigir a la selección
        $clienteManager->clearClienteActivo();
        return $this->redirectToRoute('app_cliente_seleccionar');
    }


    /**
     * Perfil del cliente
     * 
     * @param Request $request
     * @param ClienteManager $clienteManager
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/perfil', name: 'app_cliente_perfil', methods: ['GET', 'POST'])]
    public function perfil(
        Request $request,
        ClienteManager $clienteManager,
        EntityManagerInterface $entityManager
    ): Response {
        $cliente = $clienteManager->getClienteActivo();
        
        if (!$cliente) {
            $this->addFlash('error', 'Debe seleccionar un cliente para ver su perfil');
            return $this->redirectToRoute('app_cliente_seleccionar');
        }

        $form = $this->createForm(ClientePerfilType::class, $cliente);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Perfil actualizado correctamente');
                
                // Actualizar los datos en la sesión
                $clienteManager->setClienteActivo($cliente);
                
                return $this->redirectToRoute('app_cliente_perfil');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error al actualizar el perfil: ' . $e->getMessage());
            }
        }

        return $this->render('cliente/perfil.html.twig', [
            'cliente' => $cliente,
            'form' => $form->createView(),
        ]);
    }
}