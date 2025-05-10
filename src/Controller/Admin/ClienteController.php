<?php

namespace App\Controller\Admin;

use App\Entity\Cliente;
use App\Form\ClienteType;
use App\Entity\Vendedor;
use App\Entity\Localidad;
use App\Entity\Usuario;
use App\Entity\TipoUsuario;
use App\Repository\ClienteRepository;
use App\Repository\LocalidadRepository;
use App\Repository\TipoUsuarioRepository;
use App\Service\ClienteMssqlService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Controlador para la gestión de clientes.
 * 
 * Este controlador maneja todas las operaciones relacionadas con los clientes:
 * - Listado de clientes
 * - Creación de nuevos clientes
 * - Edición de clientes existentes
 * - Eliminación de clientes
 * - Asignación de vendedores
 * - Gestión de categorías de clientes
 * 
 * @package App\Controller\Admin
 * @see AdminController
 */
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

    #[Route('/nuevo', name: 'app_admin_cliente_nuevo', methods: ['GET', 'POST'])]
    public function nuevo(
        Request $request, 
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        TipoUsuarioRepository $tipoUsuarioRepository
    ): Response {
        $cliente = new Cliente();
        $form = $this->createForm(ClienteType::class, $cliente, [
            'is_new' => true
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                $this->addFlash('danger', 'Errores en el formulario: ' . implode(', ', $errors));
                return $this->render('admin/cliente/nuevo.html.twig', [
                    'cliente' => $cliente,
                    'form' => $form->createView(),
                ]);
            }

            try {
                // Verificar si se está creando un nuevo usuario
                if ($form->get('crearNuevoUsuario')->getData()) {
                    $nuevoUsuarioData = $form->get('nuevoUsuario')->getData();
                    
                    if ($nuevoUsuarioData) {
                        // Crear el nuevo usuario
                        $usuario = new Usuario();
                        $usuario->setEmail($nuevoUsuarioData->getEmail());
                        $usuario->setNombreReferencia($nuevoUsuarioData->getNombreReferencia());
                        
                        // Obtener el tipo de usuario "Cliente"
                        $tipoUsuarioCliente = $tipoUsuarioRepository->findOneBy(['codigo' => 'cliente']);
                        if (!$tipoUsuarioCliente) {
                            $this->addFlash('danger', 'No se pudo encontrar el tipo de usuario Cliente');
                            return $this->redirectToRoute('app_admin_cliente_nuevo');
                        }
                        
                        $usuario->setTipoUsuario($tipoUsuarioCliente);
                        $usuario->addRole('ROLE_CLIENTE');
                        
                        // Codificar la contraseña
                        $plainPassword = $form->get('nuevoUsuario')->get('plainPassword')->getData();
                        $hashedPassword = $passwordHasher->hashPassword($usuario, $plainPassword);
                        $usuario->setPassword($hashedPassword);
                        
                        // Guardar el usuario
                        $entityManager->persist($usuario);
                        
                        // Asignar el usuario al cliente
                        $cliente->setUsuario($usuario);
                    }
                } else {
                    // Verificar si se seleccionó un usuario existente
                    $usuarioExistente = $form->get('usuario')->getData();
                    if (!$usuarioExistente) {
                        $this->addFlash('danger', 'Debe seleccionar un usuario existente o crear uno nuevo');
                        return $this->render('admin/cliente/nuevo.html.twig', [
                            'cliente' => $cliente,
                            'form' => $form->createView(),
                        ]);
                    }
                    $cliente->setUsuario($usuarioExistente);
                }
                
                // Verificar campos requeridos
                if (!$cliente->getCodigo() || !$cliente->getRazonSocial() || !$cliente->getCategoriaImpositiva() || 
                    !$cliente->getCuit() || !$cliente->getTipoCliente() || !$cliente->getLocalidad()) {
                    $this->addFlash('danger', 'Todos los campos requeridos deben estar completos');
                    return $this->render('admin/cliente/nuevo.html.twig', [
                        'cliente' => $cliente,
                        'form' => $form->createView(),
                    ]);
                }

                // Verificar si el usuario ya está asignado a otro cliente
                $clienteExistente = $entityManager->getRepository(Cliente::class)->findOneBy(['usuario' => $cliente->getUsuario()]);
                if ($clienteExistente) {
                    $this->addFlash('danger', 'El usuario seleccionado ya está asignado a otro cliente');
                    return $this->render('admin/cliente/nuevo.html.twig', [
                        'cliente' => $cliente,
                        'form' => $form->createView(),
                    ]);
                }

                // Persistir el cliente
                $entityManager->persist($cliente);
                $entityManager->flush();

                $this->addFlash('success', 'Cliente creado correctamente');
                return $this->redirectToRoute('app_admin_cliente_index');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Error al crear el cliente: ' . $e->getMessage());
                return $this->render('admin/cliente/nuevo.html.twig', [
                    'cliente' => $cliente,
                    'form' => $form->createView(),
                ]);
            }
        }

        return $this->render('admin/cliente/nuevo.html.twig', [
            'cliente' => $cliente,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/editar', name: 'app_admin_cliente_editar', methods: ['GET', 'POST'])]
    public function editar(
        Request $request, 
        Cliente $cliente, 
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        TipoUsuarioRepository $tipoUsuarioRepository
    ): Response {
        $form = $this->createForm(ClienteType::class, $cliente, [
            'is_new' => false
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addSuccessFlash('Cliente actualizado correctamente');
            return $this->redirectToRoute('app_admin_cliente_index');
        }

        return $this->render('admin/cliente/editar.html.twig', [
            'cliente' => $cliente,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_admin_cliente_eliminar', methods: ['POST'])]
    public function eliminar(
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