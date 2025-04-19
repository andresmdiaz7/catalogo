<?php

namespace App\Controller\Admin;

use App\Entity\Cliente;
use App\Form\ClienteType;
use App\Entity\Vendedor;
use App\Entity\Localidad;
use App\Repository\ClienteRepository;
use App\Repository\LocalidadRepository;
use App\Service\ClienteMssqlService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/admin/clientes')]
class ClienteController extends AdminController
{
    #[Route('/', name: 'app_admin_cliente_index', methods: ['GET'])]
    public function index(
        Request $request, 
        ClienteRepository $clienteRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $filters = $request->query->all();
        
        // Consulta para obtener localidades únicas
        $localidades = $entityManager->getRepository(Localidad::class)
            ->createQueryBuilder('l')
            ->orderBy('l.nombre', 'ASC')
            ->getQuery()
            ->getResult();

        // Obtener vendedores
        $vendedores = $entityManager->getRepository(Vendedor::class)->findBy(
            [], 
            ['nombre' => 'ASC']
        );
        
        return $this->render('admin/cliente/index.html.twig', [
            'clientes' => $clienteRepository->findByFilters($filters),
            'filtros' => $filters,
            'localidades' => $localidades,
            'vendedores' => $vendedores
        ]);
    }

    #[Route('/nuevo', name: 'app_admin_cliente_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $cliente = new Cliente();
        $form = $this->createForm(ClienteType::class, $cliente);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($cliente);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_cliente_index');
        }

        return $this->render('admin/cliente/new.html.twig', [
            'cliente' => $cliente,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/editar', name: 'app_admin_cliente_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        Cliente $cliente, 
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $form = $this->createForm(ClienteType::class, $cliente, [
            'current_password' => $cliente->getPassword(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Solo actualizar el password si se proporcionó uno nuevo
            
            if ($newPassword = $form->get('password')->getData()) {
                $hashedPassword = $passwordHasher->hashPassword(
                    $cliente,
                    $newPassword
                );
                $cliente->setPassword($hashedPassword);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Cliente actualizado correctamente');
            return $this->redirectToRoute('app_admin_cliente_index');
        }

        return $this->render('admin/cliente/edit.html.twig', [
            'cliente' => $cliente,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_cliente_delete', methods: ['POST'])]
    public function delete(
        Request $request, 
        Cliente $cliente, 
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$cliente->getId(), $request->request->get('_token'))) {
            try {
                $entityManager->remove($cliente);
                $entityManager->flush();
                $this->addSuccessFlash('Cliente eliminado correctamente.');
            } catch (\Exception $e) {
                $this->addErrorFlash('No se puede eliminar el cliente porque tiene pedidos asociados.');
            }
        }

        return $this->redirectToRoute('app_admin_cliente_index');
    }

    #[Route('/{id}/toggle-habilitado', name: 'app_admin_cliente_toggle_habilitado', methods: ['POST'])]
    public function toggleHabilitado(
        Request $request, 
        Cliente $cliente, 
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('toggle-habilitado'.$cliente->getId(), $request->request->get('_token'))) {
            $cliente->setHabilitado(!$cliente->isHabilitado());
            $entityManager->flush();

            $this->addSuccessFlash(
                'El cliente ha sido ' . ($cliente->isHabilitado() ? 'habilitado' : 'deshabilitado')
            );
        }

        return $this->redirectToRoute('app_admin_cliente_index');
    }

    #[Route('/buscar-mssql', name: 'app_admin_cliente_buscar_mssql', methods: ['GET'])]
    public function buscarClienteMssql(
        Request $request, 
        ClienteMssqlService $clienteMssqlService,
        LocalidadRepository $localidadRepository
    ): JsonResponse {
        try {
            $codigo = $request->query->get('codigo');
            
            if (!$codigo) {
                return new JsonResponse(['error' => 'Debe proporcionar un código de cliente'], 400);
            }
            
            $clienteMssql = $clienteMssqlService->buscarClientePorCodigo($codigo);
            
            
            if (!$clienteMssql) {
                return new JsonResponse(['error' => 'Cliente no encontrado en la base de datos MSSQL'], 404);
            }
            
            // Buscar la localidad por nombre
            $localidad = null;
            if (!empty($clienteMssql['localidadNombre'])) {
                $localidad = $localidadRepository->findOneBy(['nombre' => $clienteMssql['localidadNombre']]);
            }
            
            // Agregar el ID de la localidad si existe
            if ($localidad) {
                $clienteMssql['localidad'] = $localidad->getId();
            }
            
            return new JsonResponse($clienteMssql);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Error al buscar el cliente: ' . $e->getMessage()], 500);
        }
    }
} 