<?php

namespace App\Controller\Admin;

use App\Entity\Cliente;
use App\Form\ClienteType;
use App\Repository\ClienteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Vendedor;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\Localidad;

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
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $cliente = new Cliente();
        $form = $this->createForm(ClienteType::class, $cliente);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hashear el password antes de guardar
            $hashedPassword = $passwordHasher->hashPassword(
                $cliente,
                $form->get('password')->getData()
            );
            $cliente->setPassword($hashedPassword);
            
            $entityManager->persist($cliente);
            $entityManager->flush();

            $this->addFlash('success', 'Cliente creado correctamente');
            return $this->redirectToRoute('app_admin_cliente_index');
        }

        return $this->render('admin/cliente/new.html.twig', [
            'cliente' => $cliente,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/editar', name: 'app_admin_cliente_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        Cliente $cliente, 
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $form = $this->createForm(ClienteType::class, $cliente);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Solo actualizar el password si se proporcionó uno nuevo
            if ($password = $form->get('password')->getData()) {
                $hashedPassword = $passwordHasher->hashPassword(
                    $cliente,
                    $password
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
} 